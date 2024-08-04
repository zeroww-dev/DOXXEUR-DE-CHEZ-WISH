<?php
/**
 * Script para la generación de CAPTCHAS
 *
 * @author  Jose Rodriguez <jose.rodriguez@exec.cl>
 * @license GPLv3
 * @link    http://code.google.com/p/cool-php-captcha
 * @package captcha
 * @version 0.3
 */

session_start();

if (!isset($_POST['captcha'])) {
    $_POST['captcha'] = "undefine";
}

$captcha = new SimpleCaptcha();

// OPTIONAL Change configuration...
// $captcha->wordsFile = 'words/es.php';
// $captcha->session_var = 'secretword';
// $captcha->imageFormat = 'png';
// $captcha->lineWidth = 3;
// $captcha->scale = 3; $captcha->blur = true;
// $captcha->resourcesPath = "/var/cool-php-captcha/resources";

// OPTIONAL Simple autodetect language example
/*
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $langs = array('en', 'es');
    $lang  = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if (in_array($lang, $langs)) {
        $captcha->wordsFile = "words/$lang.php";
    }
}
*/

// Image generation
$captcha->CreateImage();

/**
 * SimpleCaptcha class
 */
class SimpleCaptcha {

    /** Width of the image */
    public $width  = 200;

    /** Height of the image */
    public $height = 70;

    /** Dictionary word file (empty for random text) */
    public $wordsFile = 'words/en.php';

    /**
     * Path for resource files (fonts, words, etc.)
     *
     * "resources" by default. For security reasons, it is better to move this
     * directory to another location outside the web server
     *
     */
    public $resourcesPath = 'resources';

    /** Min word length (for non-dictionary random text generation) */
    public $minWordLength = 5;

    /**
     * Max word length (for non-dictionary random text generation)
     * 
     * Used for dictionary words indicating the word-length
     * for font-size modification purposes
     */
    public $maxWordLength = 8;

    /** Session name to store the original text */
    public $session_var = 'captcha';

    /** Background color in RGB-array */
    public $backgroundColor = array(255, 255, 255);

    /** Foreground colors in RGB-array */
    public $colors = array(
        array(27, 78, 181), // blue
        array(22, 163, 35), // green
        array(214, 36, 7),  // red
    );

    /** Shadow color in RGB-array or null */
    public $shadowColor = null; //array(0, 0, 0);

    /** Horizontal line through the text */
    public $lineWidth = 0;

    /**
     * Font configuration
     *
     * - font: TTF file
     * - spacing: relative pixel space between characters
     * - minSize: min font size
     * - maxSize: max font size
     */
    public $fonts = array(
        'Antykwa'  => array('spacing' => -3, 'minSize' => 27, 'maxSize' => 30, 'font' => 'AntykwaBold.ttf'),
        'Candice'  => array('spacing' => -1.5, 'minSize' => 28, 'maxSize' => 31, 'font' => 'Candice.ttf'),
        'DingDong' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'Ding-DongDaddyO.ttf'),
        'Duality'  => array('spacing' => -2, 'minSize' => 30, 'maxSize' => 38, 'font' => 'Duality.ttf'),
        'Heineken' => array('spacing' => -2, 'minSize' => 24, 'maxSize' => 34, 'font' => 'Heineken.ttf'),
        'Jura'     => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 32, 'font' => 'Jura.ttf'),
        'StayPuft' => array('spacing' => -1.5, 'minSize' => 28, 'maxSize' => 32, 'font' => 'StayPuft.ttf'),
        'Times'    => array('spacing' => -2, 'minSize' => 28, 'maxSize' => 34, 'font' => 'TimesNewRomanBold.ttf'),
        'VeraSans' => array('spacing' => -1, 'minSize' => 20, 'maxSize' => 28, 'font' => 'VeraSansBold.ttf'),
    );

    /** Wave configuration in X and Y axes */
    public $Yperiod    = 12;
    public $Yamplitude = 14;
    public $Xperiod    = 11;
    public $Xamplitude = 5;

    /** Letter rotation clockwise */
    public $maxRotation = 8;

    /**
     * Internal image size factor (for better image quality)
     * 1: low, 2: medium, 3: high
     */
    public $scale = 2;

    /** 
     * Blur effect for better image quality (but slower image processing).
     * Better image results with scale=3
     */
    public $blur = false;

    /** Debug? */
    public $debug = false;

    /** Image format: jpeg or png */
    public $imageFormat = 'jpeg';

    /** GD image */
    public $im;

    public function __construct($config = array()) {
    }

    public function CreateImage() {
        $ini = microtime(true);

        /** Initialization */
        $this->ImageAllocate();
        
        /** Text insertion */
        $text = $this->GetCaptchaText();
        $fontcfg  = $this->fonts[array_rand($this->fonts)];
        $this->WriteText($text, $fontcfg);

        $_SESSION[$this->session_var] = $text;

        /** Transformations */
        if (!empty($this->lineWidth)) {
            $this->WriteLine();
        }
        $this->WaveImage();
        if ($this->blur && function_exists('imagefilter')) {
            imagefilter($this->im, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $this->ReduceImage();

        if ($this->debug) {
            imagestring($this->im, 1, 1, $this->height-8,
                "$text {$fontcfg['font']} ".round((microtime(true)-$ini)*1000)."ms",
                $this->GdFgColor
            );
        }

        /** Output */
        $this->WriteImage();
        $this->Cleanup();
    }

    /**
     * Creates the image resources
     */
    protected function ImageAllocate() {
        // Check if GD is available
        if (!function_exists('imagecreatetruecolor')) {
            throw new Exception('GD library is not installed or enabled.');
        }

        // Cleanup
        if (!empty($this->im)) {
            imagedestroy($this->im);
        }

        $this->im = imagecreatetruecolor($this->width*$this->scale, $this->height*$this->scale);

        // Background color
        $this->GdBgColor = imagecolorallocate($this->im,
            $this->backgroundColor[0],
            $this->backgroundColor[1],
            $this->backgroundColor[2]
        );
        imagefilledrectangle($this->im, 0, 0, $this->width*$this->scale, $this->height*$this->scale, $this->GdBgColor);

        // Foreground color
        $color           = $this->colors[mt_rand(0, sizeof($this->colors)-1)];
        $this->GdFgColor = imagecolorallocate($this->im, $color[0], $color[1], $color[2]);

        // Shadow color
        if (!empty($this->shadowColor) && is_array($this->shadowColor) && sizeof($this->shadowColor) >= 3) {
            $this->GdShadowColor = imagecolorallocate($this->im,
                $this->shadowColor[0],
                $this->shadowColor[1],
                $this->shadowColor[2]
            );
        }
    }

    /**
     * Text generation
     *
     * @return string Text
     */
    protected function GetCaptchaText() {
        $text = $this->GetDictionaryCaptchaText();
        if (!$text) {
            $text = $this->GetRandomCaptchaText();
        }
        return $text;
    }

    /**
     * Random text generation
     *
     * @return string Text
     */
    protected function GetRandomCaptchaText($length = null) {
        if (empty($length)) {
            $length = rand($this->minWordLength, $this->maxWordLength);
        }

        $words  = "abcdefghijkmnpqrstuvwxyz23456789";
        $text   = '';
        $words  = str_split($words);
        for ($i = 0; $i < $length; $i++) {
            $text .= $words[mt_rand(0, count($words)-1)];
        }
        return $text;
    }

    /**
     * Dictionary-based text
     *
     * @return string Text
     */
    protected function GetDictionaryCaptchaText() {
        if (file_exists($this->wordsFile)) {
            $lines = file($this->wordsFile);
            return trim($lines[array_rand($lines)]);
        }
        return '';
    }

    /**
     * Write text on image
     *
     * @param string $text Text to write
     * @param array  $fontConfig Font configuration
     */
    protected function WriteText($text, $fontConfig) {
        $angle = mt_rand(-$this->maxRotation, $this->maxRotation);
        $font  = $this->resourcesPath . '/' . $fontConfig['font'];
        $size  = mt_rand($fontConfig['minSize'], $fontConfig['maxSize']);
        $box   = imagettfbbox($size, $angle, $font, $text);
        $x     = ($this->width*$this->scale - ($box[2] - $box[0])) / 2;
        $y     = ($this->height*$this->scale - ($box[1] - $box[7])) / 2;
        $y     -= $size / 3;
        imagettftext($this->im, $size, $angle, $x, $y, $this->GdFgColor, $font, $text);
    }

    /**
     * Write random lines on image
     */
    protected function WriteLine() {
        $count = 0;
        while ($count++ < 3) {
            $x1   = mt_rand(0, $this->width*$this->scale);
            $y1   = mt_rand(0, $this->height*$this->scale);
            $x2   = mt_rand(0, $this->width*$this->scale);
            $y2   = mt_rand(0, $this->height*$this->scale);
            imageline($this->im, $x1, $y1, $x2, $y2, $this->GdFgColor);
        }
    }

    /**
     * Apply wave effect to image
     */
    protected function WaveImage() {
        $this->im = imagerotate($this->im, mt_rand(-1, 1), $this->GdBgColor);
        imagefilter($this->im, IMG_FILTER_CONTRAST, -15);
    }

    /**
     * Reduce the image size
     */
    protected function ReduceImage() {
        $this->im = imagescale($this->im, $this->width, $this->height);
    }

    /**
     * Output the image
     */
    protected function WriteImage() {
        if ($this->imageFormat === 'jpeg') {
            header('Content-type: image/jpeg');
            imagejpeg($this->im);
        } else {
            header('Content-type: image/png');
            imagepng($this->im);
        }
    }

    /**
     * Clean up
     */
    protected function Cleanup() {
        imagedestroy($this->im);
    }
}
?>
