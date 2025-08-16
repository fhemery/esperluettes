<?php
/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding'           => 'UTF-8',
    'finalize'           => true,
    'ignoreNonStrings'   => false,
    'cachePath'          => storage_path('app/purifier'),
    'cacheFileMode'      => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],
        // Sanitization profile for user profile description fields (kept for backward compatibility)
        'profile' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            // Keep very basic tags if older data contains them; no external media or links
            'HTML.AllowedElements' => 'p,strong,em,ul,ol,li,br,span',
            'HTML.AllowedAttributes' => 'p.style,span.style,li.class',
            'CSS.AllowedProperties' => 'text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
            'Attr.EnableID' => false,
            'HTML.SafeIframe' => false,
            'Core.CollectErrors' => false,
        ],
        // Shared strict profile for admin-managed rich content (News, Static Pages)
        'admin-content' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            // Allow only safe, basic content elements
            'HTML.AllowedElements' => 'h1,h2,h3,h4,h5,h6,p,ul,ol,li,strong,em,b,i,a,u,s,del, blockquote,hr,br,span,img',
            'HTML.AllowedAttributes' => 'a.href,a.title,a.rel,span.style,img.src,img.alt,img.width,img.height',
            'Attr.AllowedFrameTargets' => ['_blank'],
            'Attr.AllowedRel' => ['noopener','noreferrer'],
            'CSS.AllowedProperties' => 'text-align,text-decoration',
            'URI.SafeIframeRegexp' => null,
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty' => true,
            // Security: disallow any event handlers
            'Attr.EnableID' => false,
            'HTML.SafeIframe' => false,
        ],
        'test'    => [
            'Attr.EnableID' => 'true',
        ],
        "youtube" => [
            "HTML.SafeIframe"      => 'true',
            "URI.SafeIframeRegexp" => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%",
        ],
        'custom_definition' => [
            'id'  => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],
				
				// Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
				
				// http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],
				
				// http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
					'type' => 'Text',
					'width' => 'Length',
					'height' => 'Length',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
                ]],

				// http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],
				
				// http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],

];
