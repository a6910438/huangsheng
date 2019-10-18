/* http://github.com/mindmup/bootstrap-wysiwyg */
/*global jQuery, $, FileReader*/
/*jslint browser:true*/
;(function ($) {
	"use strict";
	var readFileIntoDataUrl = function (fileInfo) {
		var loader = $.Deferred(),
			fReader = new FileReader();
		fReader.onload = function (e) {
			loader.resolve(e.target.result);
		};
		fReader.onerror = loader.reject;
		fReader.onprogress = loader.notify;
		fReader.readAsDataURL(fileInfo);
		return loader.promise();
	};
	
	$.fn.cleanHtml = function () {
		var html = $(this).html();
		return html && html.replace(/(<br>|\s|<div><br><\/div>|&nbsp;)*$/, '');
	};
	
	$.fn.wysiwyg = function (userOptions) {
		var editor = this,
			selectedRange,
			options,
			toolbarBtnSelector,
			updateToolbar = function () {
				if (options.activeToolbarClass) {
					$(options.toolbarSelector).find(toolbarBtnSelector).each(function () {
						var command = $(this).data(options.commandRole);
						if (document.queryCommandState(command)) {
							$(this).addClass(options.activeToolbarClass);
						} else {
							$(this).removeClass(options.activeToolbarClass);
						}
					});
				}
			},
			execCommand = function (commandWithArgs, valueArg) {
				var commandArr = commandWithArgs.split(' '),
					command = commandArr.shift(),
					args = commandArr.join(' ') + (valueArg || '');
				document.execCommand(command, 0, args);
				if(options.htmlEditor){
					options.htmlEditor.value = editor.html();
				}
				updateToolbar();
			},
			bindHotkeys = function (hotKeys) {
				$.each(hotKeys, function (hotkey, command) {
					editor.keydown(hotkey, function (e) {
						if (editor.attr('contenteditable') && editor.is(':visible')) {
							e.preventDefault();
							e.stopPropagation();
							execCommand(command);
						}
					}).keyup(hotkey, function (e) {
						if (editor.attr('contenteditable') && editor.is(':visible')) {
							e.preventDefault();
							e.stopPropagation();
						}
					});
				});
			},
			getCurrentRange = function () {
				var sel = window.getSelection();
				if (sel.getRangeAt && sel.rangeCount) {
					return sel.getRangeAt(0);
				}
			},
			saveSelection = function () {
				selectedRange = getCurrentRange();
			},
			restoreSelection = function () {
				var selection = window.getSelection();
				if (selectedRange) {
					try {
						selection.removeAllRanges();
					} catch (ex) {
						console.log(ex);
						document.body.createTextRange().select();
						document.selection.empty();
					}

					selection.addRange(selectedRange);
				}
			},
			insertImagesByFile = function (files,classList) {
				editor.focus();
				$.each(files, function (idx, fileInfo) {
					if (/^image\//.test(fileInfo.type)) {
						$.when(readFileIntoDataUrl(fileInfo)).done(function (dataUrl) {
							var $html = '<img';
							if(classList){
								$html += ' class="'+classList+'"';
							}
							$html += ' src="'+dataUrl+'">';
							execCommand('insertHTML', $html);
						}).fail(function (e) {
							options.fileUploadError("file-reader", e);
						});
					} else {
						options.fileUploadError("unsupported-file-type", fileInfo.type);
					}
				});
			},
			insertImageByDataUrl = function (url,classList) {
				editor.focus();
				var $html = '<img';
				if(classList){
					$html += ' class="'+classList+'"';
				}
				$html += ' src="'+url+'">';
				execCommand('insertHTML', $html);
			},
			/*
			insertVideoByUrl = function (url,classList) {
				editor.focus();
				var $html = '<video class="border';
				if(classList){
					$html += ' '+classList;
				}
				$html += '" src="'+url+'" controls="true" preload="auto"></video>';
				execCommand('insertHTML', $html);
			},
			markSelection = function (input, color) {
				restoreSelection();
				if (document.queryCommandSupported('hiliteColor')) {
					document.execCommand('hiliteColor', 0, color || 'transparent');
				}
				saveSelection();
				input.data(options.selectionMarker, color);
			},*/
			bindToolbar = function (toolbar, options) {
				toolbar.find(toolbarBtnSelector).click(function (e) {
					e.preventDefault();
					e.stopPropagation();
					restoreSelection();
					editor.focus();
					execCommand($(this).data(options.commandRole));
					saveSelection();
				});
				var fileInput = document.createElement('INPUT');
				fileInput.type = 'file';
				$(fileInput).change(function(e){
					e.stopPropagation();
					restoreSelection();
					if (this.type === 'file' && this.files && this.files.length > 0) {
						insertImagesByFile(this.files,this.dataset.editImage);
					};
					saveSelection();
					this.value = '';
				});
				toolbar.find('a[data-edit-image]').click(function (e) {
					e.preventDefault();
					e.stopPropagation();
					//判断是否是微信小程序
					if( window.wx_jssdk_ready ){
						try {
							wx.chooseImage({
								count: 1, // 默认9
								sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认二者都有 加 original 原图
								sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
								success: function (res) {
									var localId = res.localIds[0]; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
									wx.getLocalImgData({
										localId: localId, // 图片的localID
										success: function (res) {
											//var localData = res.localData; // localData是图片的base64数据，可以用img标签显示
											if (window.__wxjs_is_wkwebview) { // 如果是IOS，需要去掉前缀
												res.localData = res.localData.replace('jgp', 'jpeg');
											} else {
												res.localData = 'data:image/jpeg;base64,' + res.localData;
											}
											insertImageByDataUrl(res.localData,'d-block mw-100 my-1');
										}
									});
									
								}
							});
						} catch (error) {
							fileInput.accept = "image/*";
							fileInput.dataset.editImage = this.dataset.editImage;
							$(fileInput).click();
							//$("#page-error").append('报错');
							//$("#page-error").append(error.toString());
						}
					}else{
						fileInput.accept = "image/*";
						fileInput.dataset.editImage = this.dataset.editImage;
						$(fileInput).click();
					}
				});
				/*
				toolbar.find('a[data-edit-image]').click(function () {
					Tool.fileSelect((url)=>{
						restoreSelection();
						insertImageByUrl(url,this.dataset.editImage);
						saveSelection();
					},'image');
				});
				toolbar.find('a[data-edit-video]').click(function () {
					Tool.fileSelect((url)=>{
						restoreSelection();
						insertVideoByUrl(url,this.dataset.editVideo);
						saveSelection();
					},'video');
				});
				
				toolbar.find('a[data-edit-device]').click(function () {
					restoreSelection();
					editor.css('max-width',this.dataset.editDevice);
					saveSelection();
				});
				*/
				/*toolbar.find('[data-toggle=dropdown]').click(restoreSelection);*/
				/*
				toolbar.find('input[type=text][data-' + options.commandRole + ']').on('change', function () {
					var newValue = this.value; // ugly but prevents fake double-calls due to selection restoration
					this.value = '';
					restoreSelection();
					if (newValue) {
						editor.focus();
						execCommand($(this).data(options.commandRole), newValue);
					}
					saveSelection();
				}).on('focus', function () {
					var input = $(this);
					if (!input.data(options.selectionMarker)) {
						markSelection(input, options.selectionColor);
						input.focus();
					}
				}).on('blur', function () {
					var input = $(this);
					if (input.data(options.selectionMarker)) {
						markSelection(input, false);
					}
				});
				*/
				toolbar.find('input[type=file][data-' + options.commandRole + ']').change(function (e) {
					//e.preventDefault();
					e.stopPropagation();
					restoreSelection();
					if (this.type === 'file' && this.files && this.files.length > 0) {
						insertImages(this.files,this.dataset[options.commandRole]);
					}
					saveSelection();
					this.value = '';
				});
				toolbar.find('input[type=color][data-' + options.commandRole + ']').change(function (e) {
					e.stopPropagation();
					restoreSelection();
					editor.focus();
					execCommand($(this).data(options.commandRole), this.value);
					saveSelection();
					this.value = '';
				});

			},
			
			initFileDrops = function () {
				editor.on('dragenter dragover', false)
					.on('drop', function (e) {
						var dataTransfer = e.originalEvent.dataTransfer;
						e.stopPropagation();
						e.preventDefault();
						if (dataTransfer && dataTransfer.files && dataTransfer.files.length > 0) {
							insertImagesByFile(dataTransfer.files);
						}
					});
			};
		//document.execCommand("defaultParagraphSeparator",false,"p");
		options = $.extend({}, $.fn.wysiwyg.defaults, userOptions);
		toolbarBtnSelector = 'a[data-' + options.commandRole + '],button[data-' + options.commandRole + '],input[type=button][data-' + options.commandRole + ']';
		bindHotkeys(options.hotKeys);
		if (options.dragAndDropImages) {
			initFileDrops(); 
		}
		//$(options.toolbarSelector).prepend(options.toolbarHTML);
		bindToolbar($(options.toolbarSelector), options);
		editor.attr('contenteditable', true).css('overflow-y','auto').css('height','auto')
			.on('mouseup keyup mouseout', function () {
				if(options.htmlEditor){
					options.htmlEditor.value = editor.html();
				}
				saveSelection();
				updateToolbar();
			})
		if(options.htmlEditor){
			let initValue = editor.html();
			if(initValue){
				options.htmlEditor.value = editor.html();
			};
			editor.on('keyup', function () {
				options.htmlEditor.value = editor.html();
			})
			$(options.htmlEditor).on('keyup', function () {
				editor.html(options.htmlEditor.value);
			})
		}
		$(window).bind('touchend', function (e) {
			var isInside = (editor.is(e.target) || editor.has(e.target).length > 0),
				currentRange = getCurrentRange(),
				clear = currentRange && (currentRange.startContainer === currentRange.endContainer && currentRange.startOffset === currentRange.endOffset);
			if (!clear || isInside) {
				saveSelection();
				updateToolbar();
			}
		});
		return this;
	};
	/*扩展*/
	$.fn.wysiwygDiy = function (userOptions) {
		
		this.each(function(){
			let options = $.extend({}, $.fn.wysiwyg.defaults, userOptions);
			options.toolbarSelector = this.querySelector('.editor-toolbar');
			options.htmlEditor = this.querySelector('.editor-html-code');
			$(this).find('.editor-switch-mode').click((e)=>{
				e.preventDefault();
				e.stopPropagation();
				$(this).toggleClass('editor-html-mode');
			});
			if(this.dataset.fileUploadUrl!=undefined){
				options.fileUploadUrl = this.dataset.fileUploadUrl;
			}
			$(this.querySelector('.editor-rich-text')).wysiwyg(options);
		})

	}

	$.fn.wysiwyg.defaults = {
		hotKeys: {
			'ctrl+b meta+b': 'bold',
			'ctrl+i meta+i': 'italic',
			'ctrl+u meta+u': 'underline',
			'ctrl+z meta+z': 'undo',
			'ctrl+y meta+y meta+shift+z': 'redo',
			'ctrl+l meta+l': 'justifyleft',
			'ctrl+r meta+r': 'justifyright',
			'ctrl+e meta+e': 'justifycenter',
			'ctrl+j meta+j': 'justifyfull',
			'shift+tab': 'outdent',
			'tab': 'indent'
		},
		toolbarHTML:'',
		toolbarSelector: '.editor-toolbar',
		commandRole: 'edit',
		activeToolbarClass: 'btn-info',
		selectionMarker: 'edit-focus-marker',
		selectionColor: 'darkgrey',
		fileUploadUrl:'',
		dragAndDropImages: false,
		fileUploadError: function (reason, detail) { console.log("File upload error", reason, detail); }
	};
	//编辑框
	
}(jQuery));

$(document).ready(function () {
	$(".editor").wysiwygDiy();
});