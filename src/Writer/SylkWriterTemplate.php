<?php
    namespace Designitgmbh\MonkeyTables\Writer;

    /**
     *  Sylk Writer Template
     *      This class provides a template to write SYLK files
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
class SylkWriterTemplate
{
    public static function fileHeader()
    {
        return 'ID;PWXL;N;E
P;PGeneral
P;P0
P;P0.00
P;P#,##0
P;P#,##0.00
P;P#,##0_);;\(#,##0\)
P;P#,##0_);;[Red]\(#,##0\)
P;P#,##0.00_);;\(#,##0.00\)
P;P#,##0.00_);;[Red]\(#,##0.00\)
P;P"$"#,##0_);;\("$"#,##0\)
P;P"$"#,##0_);;[Red]\("$"#,##0\)
P;P"$"#,##0.00_);;\("$"#,##0.00\)
P;P"$"#,##0.00_);;[Red]\("$"#,##0.00\)
P;P0%
P;P0.00%
P;P0.00E+00
P;P##0.0E+0
P;P#\ ?/?
P;P#\ ??/??
P;Pd/m/yyyy
P;Pd\-mmm\-yy
P;Pd\-mmm
P;Pmmm\-yy
P;Ph:mm\ AM/PM
P;Ph:mm:ss\ AM/PM
P;Ph:mm
P;Ph:mm:ss
P;Pd/m/yyyy\ h:mm
P;Pmm:ss
P;Pmm:ss.0
P;P@
P;P[h]:mm:ss
P;P_("$"* #,##0_);;_("$"* \(#,##0\);;_("$"* "-"_);;_(@_)
P;P_(* #,##0_);;_(* \(#,##0\);;_(* "-"_);;_(@_)
P;P_("$"* #,##0.00_);;_("$"* \(#,##0.00\);;_("$"* "-"??_);;_(@_)
P;P_(* #,##0.00_);;_(* \(#,##0.00\);;_(* "-"??_);;_(@_)
P;P_-* #,##0.00\ [$'.hex2bin("1b").'(0-407]_-;;\-* #,##0.00\ [$'.hex2bin("1b").'(0-407]_-;;_-* "-"??\ [$'.hex2bin("1b").'(0-407]_-;;_-@_-
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;FCalibri;M220;L9
P;ECalibri;M220;L9
P;ECalibri Light;M360;L55
P;ECalibri;M300;SB;L55
P;ECalibri;M260;SB;L55
P;ECalibri;M220;SB;L55
P;ECalibri;M220;L18
P;ECalibri;M220;L21
P;ECalibri;M220;L61
P;ECalibri;M220;L63
P;ECalibri;M220;SB;L64
P;ECalibri;M220;SB;L53
P;ECalibri;M220;L53
P;ECalibri;M220;SB;L10
P;ECalibri;M220;L11
P;ECalibri;M220;SI;L24
P;ECalibri;M220;SB;L9
P;ECalibri;M220;L10
P;ESegoe UI;M200;L9
P;ESegoe UI;M200;SB;L9
F;P0;DG0G10;M300
B;Y3;X4;D0 0 2 3
O;L;D;V0;K47;G100 0.001
';
    }

    public static function fileEnd()
    {
        return 'E
';
    }
}
