<?php
    namespace Designitgmbh\MonkeyTables\Data;

    /**
     * A class the represents a preset of a dataset.
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oDataPreset
{
    protected /**
             * Id of the preset
             *
             * @var string
             */
    $id,
    /**
             * Name of the preset
             *
             * @var string
             */
    $name,
    /**
             * Defines if the preset is general, means available for all users
             *
             * @var boolean
             */
    $isGeneral;

    public function __construct($name, $id = null, $isGeneral = false)
    {
        if ($id == null) {
            $id = md5(time().$name);
        }

        $this->id = $id;
        $this->name = $name;
        $this->isGeneral = $isGeneral;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isGeneral()
    {
        return $this->isGeneral;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setGeneral($isGeneral = true)
    {
        if ($isGeneral) {
            $this->isGeneral = true;
        } else {
            $this->isGeneral = false;
        }

        return $this;
    }

    public function unsetGeneral()
    {
        return $this->setGeneral(false);
    }
}
