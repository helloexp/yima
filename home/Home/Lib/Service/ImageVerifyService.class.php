<?php



/**
 * 图形验证码
 * Class ImageVerifyService
 * @author Jeff.Liu<liuwy@imageco.com.cn>
 */
class ImageVerifyService
{

    public static function buildImageCodeByParam(
            $verifyName = 'verify_cj',
            $length = 4,
            $mode = 1,
            $type = 'png',
            $width = 48,
            $height = 22
    ) {
        $imageExtension = self::getImageVerify();
        switch ($imageExtension) {
            case 'imagick':
                import('@.ORG.Util.ImageBaseImagick');
                ImageBaseImagick::buildImageVerify($length, $mode, $type, $width, $height, $verifyName);
                break;
            default:
                import('ORG.Util.Image');
                Image::buildImageVerify($length, $mode, $type, $width, $height, $verifyName);
        }
    }

    public static function getImageVerify()
    {
        $configExtension = C('useImageCodeExt');
        if ($configExtension) {
            $imageExtension = $configExtension;
        } else {
            if (!defined('IMAGE_EXT')) {
                if (extension_loaded('imagick')) {
                    define('IMAGE_EXT', 'imagick');
                } else {
                    define('IMAGE_EXT', 'GD');
                }
            }
            $imageExtension = IMAGE_EXT;
        }

        return $imageExtension;
    }

    public static function buildImageCode()
    {
        if (defined('IMAGE_EXT') && IMAGE_EXT === 'imagick') {
            import('@.ORG.Util.ImageBaseImagick');
            ImageBaseImagick::buildImageVerify(
                    $length = 4,
                    $mode = 1,
                    $type = 'png',
                    $width = 48,
                    $height = 22,
                    $verifyName = 'verify_cj'
            );
        } else {
            import('ORG.Util.Image');
            Image::buildImageVerify(
                    $length = 4,
                    $mode = 1,
                    $type = 'png',
                    $width = 48,
                    $height = 22,
                    $verifyName = 'verify_cj'
            );
        }
    }

    /**
     * @param string $imageCode
     * @param string $verifyName
     * @param string $ignoreKey
     * @param string $ignoreValue
     *
     * @return bool
     */
    public static function verifyImageCode($imageCode, $verifyName = 'verify_cj', $ignoreKey = '', $ignoreValue = '')
    {
        if (defined('IMAGE_EXT') && IMAGE_EXT === 'imagick') {
            import('@.ORG.Util.ImageBaseImagick');
            return ImageBaseImagick::VerifyImageCode($verifyName, $imageCode, $ignoreKey, $ignoreValue);
        } else {
            import('ORG.Util.Image');
            return Image::VerifyImageCode($verifyName, $imageCode, $ignoreKey, $ignoreValue);
        }
    }

}


