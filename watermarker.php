<?php
/*
 * @author Noel Jarencio
 * @date 05/31/2015
 * @description This class provides functions for adding text or image overlay to a background image
 */

class Watermarker {
    private $orientation;
    private $margin;
    private $font;
    private $font_size;
    private $font_style;
    private $position;
    private $type;
    private $bg;
    private $bg_width;
    private $bg_height;

    const TYPE_TEXT = 0;
    const TYPE_IMAGE = 1;

    const POSITION_CENTER = 0;
    const POSITION_TOP_LEFT = 1;
    const POSITION_TOP_RIGHT = 2;
    const POSITION_BOTTOM_LEFT = 3;
    const POSITION_BOTTOM_RIGHT = 4;

    const ORIENTATION_HORIZONTAL = 0;
    const ORIENTATION_VERTICAL = 1;

    function __construct() {
        $this->orientation = $this::ORIENTATION_HORIZONTAL;
        $this->margin = 10;
        $this->font = 'Arial';
        $this->font_style = 'Regular';
        $this->font_size = 10;
        $this->position = $this::POSITION_CENTER;
        $this->type = $this::TYPE_TEXT;
    }

    function set_orientation($orientation) {
        $this->orientation = $orientation;
    }

    function set_margin($margin) {
        $this->margin = $margin;
    }

    function set_font($font) {
        $this->font = $font;
    }

    function set_font_style($font_style) {
        $this->font_style = $font_style;
    }

    function set_font_size($font_size) {
        $this->font_size = $font_size;
    }

    function set_position($position) {
        $this->position = $position;
    }

    function set_type($type) {
        $this->type = $type;
    }

    function set_background($url) {
        $this->bg = imagecreatefromjpeg($url);

        $this->bg_width = imagesx($this->bg);
        $this->bg_height = imagesy($this->bg);
    }

    function get_orientation() {
        return $this->orientation;
    }

    function get_margin() {
        return $this->margin;
    }

    function get_font_size() {
        return $this->font_size;
    }

    function get_font() {
        return './fonts/' . $this->font . '/' . $this->font_style;
    }

    function get_position() {
        return $this->position;
    }

    function get_type() {
        return $this->type;
    }

    function get_background() {
        return $this->bg;
    }

    static function load_fonts() {
        $ignored = array('.', '..', '.DS_Store');
        $fonts = [];

        $d = dir(dirname(__FILE__) . '/fonts');
        while (false !== ($entry = $d->read())) {
            if(in_array($entry, $ignored))
                continue;

            $fonts[$entry] = Watermarker::load_font_styles($entry);
        }
        $d->close();

        return $fonts;
    }

    static function load_font_styles($font = '') {
        $ignored = array('.', '..', '.DS_Store');
        $styles = [];

        if(empty($font))
            $font = $this->font;

        $d = dir(dirname(__FILE__) . '/fonts/' . $font);
        while (false !== ($entry = $d->read())) {
            if(in_array($entry, $ignored))
                continue;

            $file_info = pathinfo($entry);

            if($file_info['extension'] == 'ttf') {
                $key = basename(str_ireplace($font . '-', '', $entry), '.ttf');
                $styles[$key] = $entry;
            }
        }
        $d->close();

        return $styles;
    }

    function add_text($text, $color) {
        if($this->orientation == $this::ORIENTATION_HORIZONTAL)
            $angle = 0;
        else
            $angle = -90;

        # calculate maximum height of a character 
        $bbox = imagettfbbox($this->font_size, $angle, $this->get_font(), $text);
        $stamp_width = $bbox[2];
        $stamp_height = abs($bbox[7]);

        switch($this->position) {
        case $this::POSITION_TOP_LEFT:
            $x = $this->margin;
            $y = $stamp_height + $this->margin;
            break;
        case $this::POSITION_BOTTOM_LEFT:
            if($this->orientation == $this::ORIENTATION_VERTICAL) {
                $x = $this->margin;
                $y = $this->bg_height - $bbox[3] - $this->margin;
            }
            else {
                $x = $this->margin;
                $y = $this->bg_height - $this->margin;
            }
            break;
        case $this::POSITION_TOP_RIGHT:
            if($this->orientation == $this::ORIENTATION_VERTICAL) {
                $x = $this->bg_width - $bbox[6] - $this->margin;
                $y = $this->margin + $stamp_height;
            } else {
                $x = $this->bg_width - $stamp_width - $this->margin;
                $y = $this->margin + $stamp_height;
            }
            break;
        case $this::POSITION_BOTTOM_RIGHT: 
            if($this->orientation == $this::ORIENTATION_VERTICAL) {
                $x = $this->bg_width - $bbox[6] - $this->margin;
                $y = $this->bg_height - $bbox[3] - $this->margin ;
            } else {
                $x = $this->bg_width - $stamp_width - $this->margin;
                $y = $this->bg_height - $this->margin;
            }
            break;
        default:
            if($this->orientation == $this::ORIENTATION_VERTICAL) {
                $stamp_width = abs($bbox[0]);
                $stamp_height = $bbox[3];
            }
            $x = ($this->bg_width - $stamp_width) / 2;
            $y = ($this->bg_height - $stamp_height) / 2;
        }

        list($r, $g, $b) = $color;
        $rgb = imagecolorallocate($this->bg, $r, $g, $b);
        imagettftext($this->bg, $this->font_size, $angle, $x, $y, $rgb, $this->get_font(), $text);
    }

    function add_image($img, $quality = 100) {
        $image = imagecreatefromjpeg($img);
        $stamp_width = imagesx($image);
        $stamp_height = imagesy($image);

        switch($this->position) {
        case $this::POSITION_TOP_LEFT:
            $x = $this->margin;
            $y = $this->margin;
            break;
        case $this::POSITION_BOTTOM_LEFT:
            $x = $this->margin;
            $y = $this->bg_height - $stamp_height - $this->margin;
            break;
        case $this::POSITION_TOP_RIGHT:
            $x = $this->bg_width - $stamp_width - $this->margin;
            $y = $this->margin;
            break;
        case $this::POSITION_BOTTOM_RIGHT: 
            $x = $this->bg_width - $stamp_width - $this->margin;
            $y = $this->bg_height - $stamp_height - $this->margin;
            break;
        default:
            $x = ($this->bg_width - $stamp_width) / 2;
            $y = ($this->bg_height - $stamp_height) / 2;
        }

        imagecopymerge($this->bg, $image, $x, $y, 0, 0, $stamp_width, $stamp_height, $quality);
    }

    function display_image($quality = 90) {
        header("Content-Type: image/jpeg");
        imagejpeg($this->bg, null, $quality);
    }
} // class Watermarker
?>
