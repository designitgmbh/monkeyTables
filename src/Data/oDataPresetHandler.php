<?php
    namespace Designitgmbh\MonkeyTables\Data;
    
    use Designitgmbh\MonkeyTables\Http\Controllers\oDataFrameworkHelperController;

    /**
     * A basic class that handles an oDataPreset for a dataset
     * It can load, save, delete and rename presets
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oDataPresetHandler
{
    protected /**
             * Id of the dataset
             *
             * @var string
             */
    $oDataId,
    /**
             * Action for the handler
             *
             * @var string
             */
    $action,
    /**
             * The preset to handle
             *
             * @var oDataPreset
             */
    $preset,
    /**
             * The preset list
             *
             * @var Array
             */
    $presetList;

    public function __construct($oDataId, $request = null)
    {
        $this->oDataId = $oDataId;
        $this->action = null;
        $this->preset = null;
        $this->presetList = null;
        $this->isGeneral = null;
        $this->defaultPresetWasLoaded = false;

        if ($request != null) {
            $isGeneral       = $this->parseRequestVar($request, 'isGeneral');
            $this->isGeneral = ($isGeneral === true || $isGeneral == 'true') ? true : false;
        }

        $this->loadPresetList();
        if ($request != null) {
            $this->parsePresetRequest($request);
        }
    }

    public function parsePresetRequest($request)
    {
        $action         = $this->parseRequestVar($request, 'action');
        $presetId       = $this->parseRequestVar($request, 'id');
        $newPresetName  = $this->parseRequestVar($request, 'name');

        if ($presetId != null) {
            $this->loadPresetById($presetId);

            if (!$this->preset) {
                return;
            }
        }

        switch ($action) {
            case "save":
                if ($presetId == null) {
                    $this->addNewPreset($newPresetName);
                }
                break;
            case "setAsDefault":
                $this->setPresetAsDefault();
                break;
            case "unsetDefault":
                $this->unsetDefault();
                break;
            case "setAsGeneralDefault":
                $this->setPresetAsGeneralDefault();
                break;
            case "unsetGeneralDefault":
                $this->unsetGeneralDefault();
                break;
            case "rename":
                $this->renamePreset($newPresetName);
                break;
            case "delete":
                $this->deletePreset();
                $this->preset = null;
                break;
            case "loaded":
            default:
        }

        $this->action = $action;
    }

    public function isGeneral()
    {
        return $this->isGeneral;
    }

    public function isLoadAction()
    {
        return ($this->preset != null && $this->action == 'load');
    }

    public function isSaveAction()
    {
        return ($this->action == 'save');
    }

    public function isDeleteAction()
    {
        return ($this->action == 'delete');
    }

    public function isPresetActive()
    {
        return ($this->preset != null);
    }

    public function isDefaultPresetActive()
    {
        if ($this->preset) {
            return
                $this->isDefaultPresetActiveForContext(false);
        }
        return false;
    }

    public function isGeneralDefaultPresetActive()
    {
        if ($this->preset) {
            return $this->isDefaultPresetActiveForContext(true);
        }
        return false;
    }

    private function isDefaultPresetActiveForContext($context)
    {
        $this->switchContext($context);
        $index = $this->getPresetListIndexForId('default');
        if ($index == null) {
            $this->restoreContext();
            return false;
        }

        $defaultId = $this->presetList[$index]['defaultId'];
        $this->restoreContext();

        return ($defaultId == $this->preset->getId());
    }

    public function getPreset()
    {
        return $this->preset;
    }

    public function getPresetList()
    {
        $presetList = array_merge(
            $this->presetList,
            (array) $this->loadSetting("presetList", !$this->isGeneral)
        );

        usort($presetList, function ($a, $b) {
            return strcmp($a["name"], $b["name"]);
        });

        foreach ($presetList as $key => $preset) {
            if ($preset['id'] == 'default') {
                unset($presetList[$key]);
            }
        }

        return $presetList;
    }


    public function loadPresetSetting($setting)
    {
        return $this->loadSetting('_preset_'.$this->preset->getId().'_'.$setting);
    }

    public function savePresetSetting($setting, $content)
    {
        return $this->saveSetting('_preset_'.$this->preset->getId().'_'.$setting, $content);
    }

    public function deletePresetSetting($setting)
    {
        $this->saveSetting($setting, null);

        return $this;
    }


    private function loadSetting($setting, $isGeneral = null)
    {
        $name = 'oDP-'.$this->oDataId.'_'.$setting;
        $isGeneral = ($isGeneral === null) ? $this->isGeneral : $isGeneral;

        if ($isGeneral) {
            $function = "getGeneralSetting";
        } else {
            $function = "getSystemUserSetting";
        }

        return json_decode(oDataFrameworkHelperController::$function($name), true);
    }
    private function saveSetting($setting, $content)
    {
        $name = 'oDP-'.$this->oDataId.'_'.$setting;
        $encodedContent = ($content == null && !is_array($content)) ? null : json_encode($content);

        if ($this->isGeneral) {
            $function = "setGeneralSetting";
        } else {
            $function = "setSystemUserSetting";
        }

        oDataFrameworkHelperController::$function($name, $encodedContent);

        return $this;
    }

    private function loadPresetById($presetId)
    {
        $this->preset = $this->getPresetById($presetId);

        return $this;
    }

    private function getPresetById($presetId)
    {
        if ($presetId == 'default') {
            return $this->getDefaultPreset();
        } else {
            foreach ($this->presetList as $preset) {
                if ($preset['id'] == $presetId) {
                    return new oDataPreset($preset['name'], $preset['id'], $preset['isGeneral']);
                }
            }
        }
    }

    private function getDefaultPreset()
    {
        $preset = $this->getSavedDefaultPresetForContext(false);
        if ($preset === null) {
            $preset = $this->getSavedDefaultPresetForContext(true);
        }

        if ($preset === null) {
            $preset = new oDataPreset('default', 'default');
        }

        return $preset;
    }

    private function getSavedDefaultPresetForContext($context = false)
    {
        $this->switchContext($context);

        foreach ($this->presetList as $preset) {
            if ($preset['id'] == 'default') {
                if (isset($preset['defaultId']) && $preset['defaultId']) {
                    $this->switchContext($preset['defaultIsGeneral']);
                    return $this->getPresetById($preset['defaultId']);
                }
                break;
            }
        }

        $this->restoreContext();
        return null;
    }

    private function addNewPreset($newName, $newId = null)
    {
        $this->preset = new oDataPreset($newName, $newId, $this->isGeneral);
        $this->presetList[] = array(
            "id" => $this->preset->getId(),
            "name" => $this->preset->getName(),
            "isGeneral" => $this->preset->isGeneral()
        );

        $this->savePresetList();

        $this
            ->savePresetSetting(":id", $this->preset->getId())
            ->savePresetSetting(":name", $this->preset->getName())
            ->savePresetSetting(":isGeneral", $this->preset->isGeneral());
    }

    private function setPresetAsDefault()
    {
        $this->setDefault($this->preset);
    }

    private function setPresetAsGeneralDefault()
    {
        $this->setDefault($this->preset, true);
    }

    private function unsetDefault()
    {
        $this->setDefault();
    }

    private function unsetGeneralDefault()
    {
        $this->setDefault(null, true);
    }

    private function setDefault($preset = null, $context = false)
    {
        $this->switchContext($context);

        $index = $this->getPresetListIndexForId('default');
        if ($index === null) {
            $this->addNewPreset('default', 'default');
            $index = $this->getPresetListIndexForId('default');
        }

        if ($preset) {
            $id = $preset->getId();
            $isGeneral = $preset->isGeneral();
        } else {
            $id = false;
            $isGeneral = false;
        }

        $this->presetList[$index]["defaultId"] = $id;
        $this->presetList[$index]["defaultIsGeneral"] = $isGeneral;

        $this->savePresetList();

        $this->restoreContext();
    }

    private function renamePreset($newName)
    {
        $this->savePresetSetting(":name", $newName);

        foreach ($this->presetList as $key => $preset) {
            if ($preset["id"] == $this->preset->getId()) {
                $this->presetList[$key]["name"] = $newName;

                break;
            }
        }

        $this->preset->setName($newName);

        $this->savePresetList();
    }

    private function deletePreset()
    {
        $this
            ->deletePresetSetting(":id")
            ->deletePresetSetting(":name")
            ->deletePresetSetting(":isGeneral");

        foreach ($this->presetList as $key => $preset) {
            if ($preset["id"] == $this->preset->getId()) {
                unset($this->presetList[$key]);

                break;
            }
        }

        $this->savePresetList();
    }

    private function loadPresetList()
    {
        $this->presetList = $this->loadSetting("presetList");

        if ($this->presetList == null || !is_array($this->presetList)) {
            $this->presetList = array();
            $this->savePresetList();
        }
    }

    private function savePresetList()
    {
        $this->saveSetting("presetList", $this->presetList);
    }

    private function getPresetListIndexForId($presetId)
    {
        foreach ($this->presetList as $key => $preset) {
            if ($preset['id'] == $presetId) {
                return $key;
            }
        }

        return null;
    }

    private function parseRequestVar($request, $var)
    {
        if (isset($request[$var])) {
            return $request[$var];
        }

        return null;
    }

    private function switchContext($newGeneral)
    {
        if ($this->isGeneral != $newGeneral) {
            $this->oldGeneral = $this->isGeneral;

            $this->isGeneral = $newGeneral;
            $this->loadPresetList();
        }

        return $this;
    }

    private function restoreContext()
    {
        if (isset($this->oldGeneral) && $this->isGeneral != $this->oldGeneral) {
            $this->isGeneral = $this->oldGeneral;
            $this->loadPresetList();
        }

        return $this;
    }
}
