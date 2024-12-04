# Kirby plugin: Responsive images

Alpha release of the responsive images plugin for Kirby 4. This plugin provides a custom panel for your kirby installation to add breakpoints and image resolutions for your templates to have full control over your rendered images. The plugins generates a JSON file holding your configuration to your desired path.
It also lets you define a custom cropping driver to use any cropping library you want.
Additionally, it provides a custom field to select the desired image focal point for the different breakpoints.

[![Kirby 4](https://img.shields.io/badge/kirby-4-fb654f.svg)](https://getkirby.com)

[//]: # (This plugin provides a custom panel for your kirby installation to add breakpoints and image resolutions for your templates)

[//]: # (to have full control over your rendered images. The plugins generates a JSON file holding your configuration to your desired path.)

[//]: # (To set the path and other options, please see the [Options]&#40;#options&#41; section in this README file.)

[//]: # ()
[//]: # (> This plugin is completely free and published under the MIT license. However, if you are using it in a commercial project and want to help me keep up with maintenance, please consider [making a donation of your choice]&#40;https://www.paypal.me/nerdcel&#41;.)

[//]: # ()
[//]: # (## Installation)

[//]: # ()
[//]: # (### Download)

[//]: # ()
[//]: # (Download and copy this repository to `/site/plugins/responsive-images`.)

[//]: # ()
[//]: # (### Git submodule)

[//]: # ()
[//]: # (```)

[//]: # (git submodule add https://github.com/nerdcel/kirby3-responsive-images.git site/plugins/responsive-images)

[//]: # (```)

[//]: # ()
[//]: # (### Composer)

[//]: # ()
[//]: # (```)

[//]: # (composer require nerdcel/kirby3-responsive-images)

[//]: # (```)

[//]: # ()
[//]: # (## Setup)

[//]: # ()
[//]: # (![screencast-responsive-images-panel]&#40;demo.gif&#41;)

[//]: # ()
[//]: # (### Template)

[//]: # ()
[//]: # (```php)

[//]: # (<?php responsiveImage&#40;string 'teaser-home-cover', File $page->cover&#40;&#41;, string 'optional-css-class', bool true, string 'Alt text', string 'webp'&#41;;  ?>)

[//]: # (```)

[//]: # ()
[//]: # (### Output)

[//]: # ()
[//]: # (```html)

[//]: # (<picture>)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-1536x1536-crop-1.jpg" width="1536" height="1536" media="&#40;min-width: 1440px&#41; and &#40;-webkit-min-device-pixel-ratio: 1.5&#41;, &#40;min-width: 1440px&#41; and &#40;min-device-pixel-ratio: 1.5&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-1024x1024-crop-1.jpg" width="1024" height="1024" media="&#40;min-width: 1440px&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-1080x1080-crop-1.jpg" width="1080" height="1080" media="&#40;min-width: 720px&#41; and &#40;-webkit-min-device-pixel-ratio: 1.5&#41;, &#40;min-width: 720px&#41; and &#40;min-device-pixel-ratio: 1.5&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-720x720-crop-1.jpg" width="720" height="720" media="&#40;min-width: 720px&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-960x355-crop-1.jpg" width="960" height="355" media="&#40;min-width: 480px&#41; and &#40;-webkit-min-device-pixel-ratio: 1.5&#41;, &#40;min-width: 480px&#41; and &#40;min-device-pixel-ratio: 1.5&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-640x237-crop-1.jpg" width="640" height="237" media="&#40;min-width: 480px&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-720x267-crop-1.jpg" width="720" height="267" media="&#40;min-width: 0px&#41; and &#40;-webkit-min-device-pixel-ratio: 1.5&#41;, &#40;min-width: 0px&#41; and &#40;min-device-pixel-ratio: 1.5&#41;">)

[//]: # (  <source srcset="/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-480x178-crop-1.jpg" width="480" height="178" media="&#40;min-width: 0px&#41;">)

[//]: # (  <img src="http://kirby-playground.test/media/pages/photography/trees/9888d3c6b0-1674556128/monster-trees-in-the-fog-480x178-crop-1.jpg" width="480" height="178" class="" alt="Huge trees reaching into the fog" title="Huge trees reaching into the fog">)

[//]: # (</picture>)

[//]: # (```)

[//]: # ()
[//]: # (## Options)

[//]: # ()
[//]: # (The following options are available to be set using your site/config/config.php)

[//]: # ()
[//]: # (```php)

[//]: # ('nerdcel.responsive-images' => [)

[//]: # (    'cache' => true,)

[//]: # (    'configPath' => kirby&#40;&#41;->root&#40;'content'&#41;,)

[//]: # (    'configFile' => 'responsive-img.json',)

[//]: # (    'quality' => 75,)

[//]: # (    'defaultWidth' => 1024,)

[//]: # (    'allowedRoles' => [)

[//]: # (        'admin')

[//]: # (    ],)

[//]: # (    'cropDriver' => function &#40;$file, $options&#41; {)

[//]: # (        return $file->focusCrop&#40;)

[//]: # (            $options['width'],)

[//]: # (            $options['height'],)

[//]: # (            [)

[//]: # (                'quality' => $options['quality'],)

[//]: # (                'upscale' => $options['upscale'],)

[//]: # (                'format' => $options['format'],)

[//]: # (            ])

[//]: # (        &#41;;)

[//]: # (    })

[//]: # (])

[//]: # (```)

[//]: # ()
[//]: # (### Image driver)

[//]: # (In favor of the upcoming Kirby 4 release, the "crop" driver is now a callback function. This allows you to use any cropping driver you want.)

[//]: # (For example if you are using the flokosiol/kirby-focus plugin, you can use the driver shown above.)

[//]: # (In general the driver expects a callback function with the following signature:)

[//]: # (```php)

[//]: # (function &#40;File $file, array $options&#41; {)

[//]: # (    // do something with the file and return it)

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (## Development)

[//]: # ()
[//]: # (Frontend components are based on kirby's internal UI Kit. Development works using the kirbyup npm module.)

[//]: # (To start developing simply run the following cmd from the plugin root:)

[//]: # (```shell)

[//]: # (npm run dev)

[//]: # (```)

[//]: # ()
[//]: # (If that doesn't work, rund ```npm install``` first.)

[//]: # ()
[//]: # (## License)

[//]: # ()
[//]: # (MIT)

[//]: # ()
[//]: # (## Credits)

[//]: # ()
[//]: # (- [Marcel Hieke]&#40;https://github.com/nerdcel&#41;)
