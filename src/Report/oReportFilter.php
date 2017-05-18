<?php
    namespace Designitgmbh\MonkeyTables\Report;

    use Designitgmbh\MonkeyTables\Data\oDataChain;

    /**
     * A filter for a series. It contains the label and the valueKey (link to valueKey..).
     * You can also add the type of the filter.
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class oReportFilter extends oDataChain
{
    /**
         * Constructor.
         *
         * @param string                        $label          The label of the filter.
         * @param string                        $valueKey       The value key for the filter.
         * @param string                        $type           The type of the filter.
         */
    public function __construct($label, $valueKey, $type = null)
    {
        parent::__construct($label, $valueKey);

        $this->setFilterable(true);
        $this->setType($type);
        $this->setHasAutoFilterValues(true);
    }
}
