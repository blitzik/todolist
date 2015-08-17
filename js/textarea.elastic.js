/**
 *	@name							Elastic
 *	@descripton						Elastic is jQuery plugin that grow and shrink your textareas automatically
 *	@version						1.6.11
 *	@requires						jQuery 1.2.6+
 *
 *	@author							Jan Jarfalk
 *	@author-email					jan.jarfalk@unwrongest.com
 *	@author-website					http://www.unwrongest.com
 *
 *	@licence						MIT License - http://www.opensource.org/licenses/mit-license.php
 */
!function(){jQuery.fn.extend({elastic:function(){var e=["paddingTop","paddingRight","paddingBottom","paddingLeft","fontSize","lineHeight","fontFamily","width","fontWeight","border-top-width","border-right-width","border-bottom-width","border-left-width","borderTopStyle","borderTopColor","borderRightStyle","borderRightColor","borderBottomStyle","borderBottomColor","borderLeftStyle","borderLeftColor"];return this.each(function(){function t(){var e=Math.floor(parseInt(o.width(),10));n.width()!==e&&(n.css({width:e+"px"}),i(!0))}function r(e,t){var r=Math.floor(parseInt(e,10));o.height()!==r&&o.css({height:r+"px",overflow:t})}function i(e){var t=o.val().replace(/&/g,"&amp;").replace(/ {2}/g,"&nbsp;").replace(/<|>/g,"&gt;").replace(/\n/g,"<br />"),i=n.html().replace(/<br>/gi,"<br />");if((e||t+"&nbsp;"!==i)&&(n.html(t+"&nbsp;"),Math.abs(n.height()+h-o.height())>3)){var s=n.height()+h;s>=a?r(a,"auto"):d>=s?r(d,"hidden"):r(s,"hidden")}}if("textarea"!==this.type)return!1;var o=jQuery(this),n=jQuery("<div />").css({position:"absolute",display:"none","word-wrap":"break-word","white-space":"pre-wrap"}),h=parseInt(o.css("line-height"),10)||parseInt(o.css("font-size"),"10"),d=parseInt(o.css("height"),10)||3*h,a=parseInt(o.css("max-height"),10)||Number.MAX_VALUE;0>a&&(a=Number.MAX_VALUE),n.appendTo(o.parent());for(var s=e.length;s--;)n.css(e[s].toString(),o.css(e[s].toString()));o.css({overflow:"hidden"}),o.bind("keyup change cut paste",function(){i()}),jQuery(window).bind("resize",t),o.bind("resize",t),o.bind("update",i),o.bind("blur",function(){n.height()<a&&o.height(n.height()>d?n.height():d)}),o.bind("input paste",function(){setTimeout(i,250)}),i()})}})}(jQuery);