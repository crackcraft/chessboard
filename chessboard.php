<?php
/**********************************************************************************
 * chessboard
 * @version 0.1
 * @CHESSBOARD copyright (c) 2014 crackcraft
 * @license GNU GPLv2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author crackcraft@gmail.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Version History:
 *
 * 0.1: Initial version
 **********************************************************************************/

 
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

jimport( 'joomla.event.plugin' );

class plgContentChessBoard extends JPlugin {
	/**
	* Plugin integrates chessboard.js in an article content.
	* Inspired by EmbedChessboard
	*
	* <b>Usage:</b>
	* [chessboard]position: "FEN string", other config options[/chessboard]
	*
	*/
	function plgContentChessBoard(&$subject, $params) {
		parent::__construct( $subject, $params );
 	}

	function htmlToText( $string ) {
		$string = preg_replace( '/<.+?>/s', '', $string );
		$string = preg_replace( array('/&lt;/i', '/&gt;/i', '/&quot;/i', '/&amp;/i'), array('<', '>', '"', '&'), $string );
		return $string;
	}

	function parseAttrs( $string ) {
		$attr = array();
		$result = array();

		preg_match_all('/([\w:-]+)[\s]?=[\s]?((["\']).*?\\3)/i', $string, $attr);
		
		if (is_array($attr)) {
			$n = count($attr[1]);
			for ($i = 0; $i < $n; $i++) {
				$result[$attr[1][$i]] = $attr[2][$i];
			}
		}

		return $result;
	}

	function formatAttrs( $attrs ) {
		$result = '';
		while (list($key, $val) = each($attrs)) {
			$result .= ' '.$key.'='.$val;
		}

		return $result;
	}

	function onContentPrepare( $context, &$row, &$params, $limitstart=0 ) {
		$plugin	= &JPluginHelper::getPlugin( 'Content', 'chessboard' );

		if ( JString::strpos( $row->text, 'chessboard' ) === false ) {
			return true;
		}

		$regex = "#\[chessboard\b(.*?)\](.*?)\[/chessboard\]#s";

		if ( !$this->params->get( 'enabled', 1 ) ) {
			$row->text = preg_replace( $regex, '', $row->text );
			return true;
		}

		preg_match_all( $regex, $row->text, $matches );
		$count = count( $matches[0] );
		if ($count) {
			$pluginbase = ''.JURI::base(true).'/plugins/content/chessboard/';
			$document= &JFactory::getDocument();
			$document->addStyleSheet( $pluginbase.'css/chessboard-0.3.0.min.css' );
			$document->addScript($pluginbase.'js/chessboard-0.3.0.min.js');
			$tagid_prefix = $this->params->def('tagid');
			$default_attrs = $this->parseAttrs($this->params->def('attrs'));
			$default_config = $this->params->def('config');
			$document->addScriptDeclaration('$ = jQuery.noConflict();');
        
			for ( $i=0; $i < $count; $i++ ) {
				$attrs = array_merge($default_attrs, $this->parseAttrs($this->htmlToText($matches[1][$i])));
				$config = $this->htmlToText($matches[2][$i]);
				$replace[$i] = $regex;
				$tagid = uniqid($tagid_prefix);
				$boards[$i] = '<div id="'.$tagid.'"'.$this->formatAttrs($attrs).'></div>';
				$document->addScriptDeclaration('$(function() { new ChessBoard("'.$tagid.'", $.extend({'.$default_config.'},{'.$config.'})); });');
			}
        
			$row->text = preg_replace( $replace, $boards, $row->text );
		}
	}

}
?>