<?php
    namespace Designitgmbh\MonkeyTables\Writer;

    /**
     *  Sylk Writer Template
     *      This class converts SYLK strings to and from UTF8
     *
     * @package    MonkeyTables
     * @author     Philipp Pajak <p.pajak@design-it.de>
     * @license    https://raw.githubusercontent.com/designitgmbh/monkeyTables/master/LICENSE  BSD
     */
    Class SylkWriterString
    {
        private static $characterMap = [];

        public static function fromUTF8($string)
        {
            self::generateCharacterMap();
            return str_replace(
                array_values(self::$characterMap),
                array_keys(self::$characterMap),
                $string
            );
        }

        public static function toUTF8($string) {
            self::generateCharacterMap();
            return str_replace(
                array_keys(self::$characterMap),
                array_values(self::$characterMap),
                $string
            );
        }
    
        private static function generateCharacterMap()
        {
            if(!empty(self::$characterMap))
                return;

            self::$characterMap = array(
                "\x1B 0"  => chr(0),
                "\x1B 1"  => chr(1),
                "\x1B 2"  => chr(2),
                "\x1B 3"  => chr(3),
                "\x1B 4"  => chr(4),
                "\x1B 5"  => chr(5),
                "\x1B 6"  => chr(6),
                "\x1B 7"  => chr(7),
                "\x1B 8"  => chr(8),
                "\x1B 9"  => chr(9),
                "\x1B :"  => chr(10),
                "\x1B ;"  => chr(11),
                "\x1B <"  => chr(12),
                "\x1B :"  => chr(13),
                "\x1B >"  => chr(14),
                "\x1B ?"  => chr(15),
                "\x1B!0"  => chr(16),
                "\x1B!1"  => chr(17),
                "\x1B!2"  => chr(18),
                "\x1B!3"  => chr(19),
                "\x1B!4"  => chr(20),
                "\x1B!5"  => chr(21),
                "\x1B!6"  => chr(22),
                "\x1B!7"  => chr(23),
                "\x1B!8"  => chr(24),
                "\x1B!9"  => chr(25),
                "\x1B!:"  => chr(26),
                "\x1B!;"  => chr(27),
                "\x1B!<"  => chr(28),
                "\x1B!="  => chr(29),
                "\x1B!>"  => chr(30),
                "\x1B!?"  => chr(31),
                "\x1B'?"  => chr(127),
                "\x1B(0"  => '€',
                "\x1B(2"  => '‚',
                "\x1B(3"  => 'ƒ',
                "\x1B(4"  => '„',
                "\x1B(5"  => '…',
                "\x1B(6"  => '†',
                "\x1B(7"  => '‡',
                "\x1B(8"  => 'ˆ',
                "\x1B(9"  => '‰',
                "\x1B(:"  => 'Š',
                "\x1B(;"  => '‹',
                "\x1BNj"  => 'Œ',
                "\x1B(>"  => 'Ž',
                "\x1B)1"  => '‘',
                "\x1B)2"  => '’',
                "\x1B)3"  => '“',
                "\x1B)4"  => '”',
                "\x1B)5"  => '•',
                "\x1B)6"  => '–',
                "\x1B)7"  => '—',
                "\x1B)8"  => '˜',
                "\x1B)9"  => '™',
                "\x1B):"  => 'š',
                "\x1B);"  => '›',
                "\x1BNz"  => 'œ',
                "\x1B)>"  => 'ž',
                "\x1B)?"  => 'Ÿ',
                "\x1B*0"  => ' ',
                "\x1BN!"  => '¡',
                "\x1BN\"" => '¢',
                "\x1BN#"  => '£',
                "\x1BN("  => '¤',
                "\x1BN%"  => '¥',
                "\x1B*6"  => '¦',
                "\x1BN'"  => '§',
                "\x1BNH " => '¨',
                "\x1BNS"  => '©',
                "\x1BNc"  => 'ª',
                "\x1BN+"  => '«',
                "\x1B*<"  => '¬',
                "\x1B*="  => '­' ,
                "\x1BNR"  => '®',
                "\x1B*?"  => '¯',
                "\x1BN0"  => '°',
                "\x1BN1"  => '±',
                "\x1BN2"  => '²',
                "\x1BN3"  => '³',
                "\x1BNB " => '´',
                "\x1BN5"  => 'µ',
                "\x1BN6"  => '¶',
                "\x1BN7"  => '·',
                "\x1B+8"  => '¸',
                "\x1BNQ"  => '¹',
                "\x1BNk"  => 'º',
                "\x1BN;"  => '»',
                "\x1BN<"  => '¼',
                "\x1BN="  => '½',
                "\x1BN>"  => '¾',
                "\x1BN?"  => '¿',
                "\x1BNAA" => 'À',
                "\x1BNBA" => 'Á',
                "\x1BNCA" => 'Â',
                "\x1BNDA" => 'Ã',
                "\x1BNHA" => 'Ä',
                "\x1BNJA" => 'Å',
                "\x1BNa"  => 'Æ',
                "\x1BNKC" => 'Ç',
                "\x1BNAE" => 'È',
                "\x1BNBE" => 'É',
                "\x1BNCE" => 'Ê',
                "\x1BNHE" => 'Ë',
                "\x1BNAI" => 'Ì',
                "\x1BNBI" => 'Í',
                "\x1BNCI" => 'Î',
                "\x1BNHI" => 'Ï',
                "\x1BNb"  => 'Ð',
                "\x1BNDN" => 'Ñ',
                "\x1BNAO" => 'Ò',
                "\x1BNBO" => 'Ó',
                "\x1BNCO" => 'Ô',
                "\x1BNDO" => 'Õ',
                "\x1BNHO" => 'Ö',
                "\x1B-7"  => '×',
                "\x1BNi"  => 'Ø',
                "\x1BNAU" => 'Ù',
                "\x1BNBU" => 'Ú',
                "\x1BNCU" => 'Û',
                "\x1BNHU" => 'Ü',
                "\x1B-="  => 'Ý',
                "\x1BNl"  => 'Þ',
                "\x1BN{"  => 'ß',
                "\x1BNAa" => 'à',
                "\x1BNBa" => 'á',
                "\x1BNCa" => 'â',
                "\x1BNDa" => 'ã',
                "\x1BNHa" => 'ä',
                "\x1BNJa" => 'å',
                "\x1BNq"  => 'æ',
                "\x1BNKc" => 'ç',
                "\x1BNAe" => 'è',
                "\x1BNBe" => 'é',
                "\x1BNCe" => 'ê',
                "\x1BNHe" => 'ë',
                "\x1BNAi" => 'ì',
                "\x1BNBi" => 'í',
                "\x1BNCi" => 'î',
                "\x1BNHi" => 'ï',
                "\x1BNs"  => 'ð',
                "\x1BNDn" => 'ñ',
                "\x1BNAo" => 'ò',
                "\x1BNBo" => 'ó',
                "\x1BNCo" => 'ô',
                "\x1BNDo" => 'õ',
                "\x1BNHo" => 'ö',
                "\x1B/7"  => '÷',
                "\x1BNy"  => 'ø',
                "\x1BNAu" => 'ù',
                "\x1BNBu" => 'ú',
                "\x1BNCu" => 'û',
                "\x1BNHu" => 'ü',
                "\x1B/="  => 'ý',
                "\x1BN|"  => 'þ',
                "\x1BNHy" => 'ÿ',
            );
        }
    }