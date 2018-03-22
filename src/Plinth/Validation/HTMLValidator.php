<?php

namespace Plinth\Validation;

class HTMLValidator
{
    const   RULE_ALLOW_FULL = 'html_full',
            RULE_ALLOW_TAGS = 'html_allow',
            RULE_DENY_TAGS = 'html_deny';
    
    /*
     * Updated 9-3-2015
     *  
     * https://developer.mozilla.org/en-US/docs/Web/HTML/Element 
     */
    private $_tags = [
        'html', //Basic
        'head', 'link', 'base', 'meta', 'style', 'title', //Document metadata
        'address', 'article', 'body', 'footer', 'header', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hgroup', 'nav', 'section', //Content sectioning
        'blockquote', 'dd', 'div', 'dl', 'dt', 'figcaption', 'figure', 'hr', 'li', 'main', 'ol', 'p', 'pre', 'ul', //Text content
        'a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr', //Inline text semantics
        'area', 'audio', 'img', 'map', 'track', 'video', //Image & multimedia
        'embed', 'iframe', 'object', 'param', 'source', //Embedded content
        'canvas', 'noscript', 'script', //Scripting
        'del', 'ins', //Edits
        'caption', 'col', 'colgroup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', //Table content
        'button', 'datalist', 'fieldset', 'form', 'input', 'keygen', 'label', 'legend', 'meter', 'optgroup', 'option', 'output', 'progress', 'select', 'textarea', //Forms
        'details', 'dialog', 'menu', 'menuitem', 'summary', //Interactive elements
        'content', 'decorator', 'element', 'shadow', 'template' //Web Components
    ];

	/**
	 * @var array
	 */
    private $_rule_full_tags = ['html', 'head', 'link', 'base', 'meta', 'style', 'title', 'body'];

	/**
	 * @var array
	 */
    private $_defaultSettings = [self::RULE_ALLOW_FULL => false];

	/**
	 * @var array
	 */
    private $_rules;

	/**
	 * HTMLValidator constructor.
	 * @param $rules
	 */
    public function __construct($rules)
	{
        $this->_rules = array_merge($this->_defaultSettings, $rules);
    }

	/**
	 * @param $html
	 * @return mixed|string
	 */
    public function filter($html)
	{
        //Strip CDATA from TinyMCE
        $html = str_replace(['// <![CDATA[','// ]]>'], '', $html);
                
        if (count($this->_rules) > 0) {
            $html = strip_tags($html, $this->calculateTags());
        }
                
        return $html;
    }

	/**
	 * @return string
	 */
    private function calculateTags()
	{
        // Use array_filter
        $strip = true;
        $tags = [];
        
        foreach ($this->_rules as $rule => $ruleValue) {
            switch ($rule) {
                case self::RULE_ALLOW_FULL:
                    if ($ruleValue === false) {
                        $tags = array_merge($tags, $this->_rule_full_tags);
                    }
                    break;
                    
                case self::RULE_DENY_TAGS:
                    if (is_array($ruleValue)) {
                        $tags = array_merge($tags, $ruleValue);
                    }
                    break;
                case self::RULE_ALLOW_TAGS: 
                    if (is_array($ruleValue)) {
                        $strip = false;
                        $tags = $ruleValue;
                        break 2;
                    } else {
                        break;
                    }                
            }
        }
                
        if ($strip === true) {
            $tags = array_diff($this->_tags, $tags);
        }
                
        return '<' . implode('><', $tags) . '>';
    }
}