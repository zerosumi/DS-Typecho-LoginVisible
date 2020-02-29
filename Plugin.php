<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章部分内容登录可见
 * 
 * @package LoginVisible
 * @author zerosumi
 * @version 0.7
 * @link https://www.zerosumi.com
 */
class LoginVisible_Plugin implements Typecho_Plugin_Interface{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('LoginVisible_Plugin', 'renderPage');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('LoginVisible_Plugin', 'addButton');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('LoginVisible_Plugin', 'addButton');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 内容处理
     * 
     * @access public
     * @param array $value
     * @param Widget_Abstract_Contents $contents
     * @return string
     */
    public static function renderPage($value, Widget_Abstract_Contents $contents){
        if (defined('__TYPECHO_ADMIN__')) return $value;
        if ($value['type'] != 'page' && $value['type'] != 'post') return $value;
        $user = Typecho_Widget::widget('Widget_User');
		$value['text'] = preg_replace_callback(
            '/'.self::get_shortcode_regex('loginhidden').'/', 
            function($matches) use ($user, $value, $siteUrl, $loginUrl) {
                if ($matches[1] == '[' && $matches[6] == ']') return substr($matches[0], 1, -1); //排除双重括号的代码
                if ($user->hasLogin()) {
                	$inner = $matches[5];
                	$placeholder = '<div style="text-align: center; border: 1px dashed #FF0000; padding: 20px; margin: 10px auto;"><span style="color: #FF0000;">以下内容登录用户可见：<br></span>'.$inner.'</div>';
                } else {
                    $loginUrl = Typecho_Widget::widget('Widget_Options')->loginUrl;
                	$redirectUrl = $loginUrl.'?referer='.urlencode($value['permalink']);
                	$placeholder = '<div style="text-align: center; border: 1px dashed #FF0000; padding: 20px; margin: 10px auto; color: #FF0000;">此处内容<a href="'.$redirectUrl.'" target="_self">登录</a>后可见</div>';
                }
                if ($value['isMarkdown']) $placeholder = "\n!!!\n{$placeholder}\n!!!\n";
                return $placeholder;
            },
            $value['text']
        );
        return $value;
    }

    /**
     * 获取匹配短代码的正则表达式
     * @param string $tagnames
     * @return string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L254
     */
    public static function get_shortcode_regex( $tagname ) {
        $tagregexp = preg_quote( $tagname );
        // WARNING! Do not change this regex without changing do_shortcode_tag() and strip_shortcode_tag()
        // Also, see shortcode_unautop() and shortcode.js.
        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
        return
            '\\['                                // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($tagregexp)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            .     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            .     '(?:'
            .         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            .         '[^\\]\\/]*'               // Not a closing bracket or forward slash
            .     ')*?'
            . ')'
            . '(?:'
            .     '(\\/)'                        // 4: Self closing tag ...
            .     '\\]'                          // ... and closing bracket
            . '|'
            .     '\\]'                          // Closing bracket
            .     '(?:'
            .         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            .             '[^\\[]*+'             // Not an opening bracket
            .             '(?:'
            .                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            .                 '[^\\[]*+'         // Not an opening bracket
            .             ')*+'
            .         ')'
            .         '\\[\\/\\2\\]'             // Closing shortcode tag
            .     ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
        // phpcs:enable
    }

    /**
     * 获取短代码属性数组
     * @param $text
     * @return array|string
     * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/shortcodes.php#L508
     */
    public static function shortcode_parse_atts($text) {
        $atts    = array();
        $pattern = self::get_shortcode_atts_regex();
        $text    = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $text );
        if ( preg_match_all( $pattern, $text, $match, PREG_SET_ORDER ) ) {
            foreach ( $match as $m ) {
                if ( ! empty( $m[1] ) ) {
                    $atts[ strtolower( $m[1] ) ] = stripcslashes( $m[2] );
                } elseif ( ! empty( $m[3] ) ) {
                    $atts[ strtolower( $m[3] ) ] = stripcslashes( $m[4] );
                } elseif ( ! empty( $m[5] ) ) {
                    $atts[ strtolower( $m[5] ) ] = stripcslashes( $m[6] );
                } elseif ( isset( $m[7] ) && strlen( $m[7] ) ) {
                    $atts[] = stripcslashes( $m[7] );
                } elseif ( isset( $m[8] ) && strlen( $m[8] ) ) {
                    $atts[] = stripcslashes( $m[8] );
                } elseif ( isset( $m[9] ) ) {
                    $atts[] = stripcslashes( $m[9] );
                }
            }
            // Reject any unclosed HTML elements
            foreach ( $atts as &$value ) {
                if ( false !== strpos( $value, '<' ) ) {
                    if ( 1 !== preg_match( '/^[^<]*+(?:<[^>]*+>[^<]*+)*+$/', $value ) ) {
                        $value = '';
                    }
                }
            }
        } else {
            $atts = ltrim( $text );
        }
        return $atts;
    }

    private static function get_shortcode_atts_regex(){return '/([\w-]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w-]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w-]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|\'([^\']*)\'(?:\s|$)|(\S+)(?:\s|$)/';}

    /**
     * 编辑页添加相关按钮
     * 
     * @static
     * @access public
     * @return void
     */
    public static function addButton()
	{ ?>
		<style>.wmd-button-row {
	    	height: auto;
		}</style>
		<script> 
            $(document).ready(function() {
          	    $('#wmd-button-row').append('<li class="wmd-button" id="wmd-login-visible-button" title="登录可见"><span style="background: none; font-size: 10px; text-align: center; color: #999999; font-family: serif;">登录可见</span></li>');
				if ($('#wmd-button-row').length !== 0) {
					$('#wmd-login-visible-button').click(function() {
						insertTag();
					})
				}
				function insertTag() {
					var tagStart = "[loginhidden]\n";
					var tagPlaceholder = "此处输入登录可见内容\n";
					var tagEnd = "[/loginhidden]\n";
					var myField;
					if (document.getElementById('text') && document.getElementById('text').type == 'textarea') {
						myField = document.getElementById('text');
					} else {
						return false;
					}
					if (document.selection) {
						myField.focus();
						sel = document.selection.createRange();
						sel.text = tagStart + tagPlaceholder + tagEnd;
						myField.focus();
					} else if (myField.selectionStart || myField.selectionStart == '0') {
						var startPos = myField.selectionStart;
						var endPos = myField.selectionEnd;
						var cursorPos = startPos;
						if (startPos === endPos) {
							myField.value = myField.value.substring(0, startPos)
							+ tagStart + tagPlaceholder + tagEnd
							+ myField.value.substring(endPos, myField.value.length);
							myField.focus();
							myField.selectionStart = cursorPos + tagStart.length;
							myField.selectionEnd = cursorPos + tagStart.length + tagPlaceholder.length - 1;
						} else {
							var selectionContent = myField.value.substring(startPos, endPos);
							myField.value = myField.value.substring(0, startPos)
							+ tagStart
							+ selectionContent + '\n'
							+ tagEnd
							+ myField.value.substring(endPos, myField.value.length);
							myField.focus();
							myField.selectionStart = cursorPos + tagStart.length;
							myField.selectionEnd = cursorPos + tagStart.length + selectionContent.length;
						}
					} else {
						myField.value += (tagStart + tagPlaceholder + tagEnd);
						myField.focus();
					}
				}
			});
		</script>
	<?php
	}
}
