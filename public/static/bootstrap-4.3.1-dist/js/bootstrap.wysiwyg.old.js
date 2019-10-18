/* http://github.com/mindmup/bootstrap-wysiwyg */
/*global jQuery, $, FileReader*/
/*jslint browser:true*/
(function ($) {
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
						$.uploadFileGetUrl(fileInfo,options.fileUploadUrl,function(redata){
							var $html = '<img';
							if(classList){
								$html += ' class="'+classList+'"';
							}
							$html += ' src="'+redata.url+'">';
							execCommand('insertHTML', $html);
						})
					} else {
						options.fileUploadError("unsupported-file-type", fileInfo.type);
					}
				});
			},
			insertImageByUrl = function (url,classList) {
				editor.focus();
				var $html = '<img';
				if(classList){
					$html += ' class="'+classList+'"';
				}
				$html += ' src="'+url+'">';
				execCommand('insertHTML', $html);
			},
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
			},
			bindToolbar = function (toolbar, options) {
				toolbar.find(toolbarBtnSelector).click(function () {
					restoreSelection();
					editor.focus();
					execCommand($(this).data(options.commandRole));
					saveSelection();
				});
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
				toolbar.find('[data-toggle=dropdown]').click(restoreSelection);

				toolbar.find('input[type=text][data-' + options.commandRole + ']').on('change', function () {
					var newValue = this.value; /* ugly but prevents fake double-calls due to selection restoration */
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
				toolbar.find('input[type=file][data-' + options.commandRole + ']').change(function () {
					restoreSelection();
					if (this.type === 'file' && this.files && this.files.length > 0) {
						insertImagesByFile(this.files,this.dataset.classList);
					}
					saveSelection();
					this.value = '';
				});
				toolbar.find('input[type=color][data-' + options.commandRole + ']').change(function () {
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
		document.execCommand("defaultParagraphSeparator",false,"p");
		options = $.extend({}, $.fn.wysiwyg.defaults, userOptions);
		toolbarBtnSelector = 'a[data-' + options.commandRole + '],button[data-' + options.commandRole + '],input[type=button][data-' + options.commandRole + ']';
		bindHotkeys(options.hotKeys);
		if (options.dragAndDropImages) {
			initFileDrops(); 
		}
		$(options.toolbarSelector).prepend(options.toolbarHTML);
		bindToolbar($(options.toolbarSelector), options);
		editor.attr('contenteditable', true)
			.on('mouseup keyup mouseout', function () {
				if(options.htmlEditor){
					options.htmlEditor.value = editor.html();
				}
				saveSelection();
				updateToolbar();
			})
		if(options.htmlEditor){
			editor.on('keyup', function () {
				if(options.htmlEditor){
					options.htmlEditor.value = editor.html();
				}
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
			options.toolbarSelector = this.querySelector('.btn-toolbar');
			options.htmlEditor = this.querySelector('textarea');
			if(this.dataset.fileUploadUrl!=undefined){
				options.fileUploadUrl = this.dataset.fileUploadUrl;
			}
			$(this.querySelector('div.editor')).wysiwyg(options);
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
		toolbarHTML:'\
		<div class="bg-white btn-group btn-group-sm border border-info mb-1"> \
		<button class="bg-white btn dropdown-toggle" data-toggle="dropdown" title="字体"> \
			<svg baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">\
				<path d="M9.93 13.5h4.14L12 7.98zM20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-4.05 16.5l-1.14-3H9.17l-1.12 3H5.96l5.11-13h1.86l5.11 13h-2.09z"/>\
			</svg>\
		</button>\
		<ul class="dropdown-menu">\
			<li class="dropdown-item pointer"><a data-edit="fontName Microsoft YaHei UI" style="font-family:\'Microsoft YaHei\'">微软雅黑 UI</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName KaiTi" style="font-family:\'KaiTi\'">楷体</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName SimSun" style="font-family:\'SimSun\'">宋体</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName FangSong" style="font-family:\'FangSong\'">仿宋</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName NSimSun" style="font-family:\'NSimSun\'">新宋体</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName SimHei" style="font-family:\'SimHei\'">黑体</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Microsoft JhengHei" style="font-family:\'Microsoft JhengHei\'">微软正黑体</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Serif" style="font-family:\'Serif\'">Serif</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Sans" style="font-family:\'Sans\'">Sans</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Arial" style="font-family:\'Arial\'">Arial</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Arial Black" style="font-family:\'Arial Black\'">Arial Black</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Courier" style="font-family:\'Courier\'">Courier</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Courier New" style="font-family:\'Courier New\'">Courier New</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Comic Sans MS" style="font-family:\'Comic Sans MS\'">Comic Sans MS</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Helvetica" style="font-family:\'Helvetica\'">Helvetica</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Impact" style="font-family:\'Impact\'">Impact</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Lucida Grande" style="font-family:\'Lucida Grande\'">Lucida Grande</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Lucida Sans" style="font-family:\'Lucida Sans\'">Lucida Sans</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Tahoma" style="font-family:\'Tahoma\'">Tahoma</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Times" style="font-family:\'Times\'">Times</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Times New Roman" style="font-family:\'Times New Roman\'">Times New Roman</a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontName Verdana" style="font-family:\'Verdana\'">Verdana</a></li></ul>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<button class="bg-white btn dropdown-toggle" data-toggle="dropdown" title="字体大小">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M1 9h3v6h2V9h3V7H1v2zm6-6v2h4v10h2V5h4V3H7z"/>\
			</svg>\
		</button>\
		<ul class="dropdown-menu">\
			<li class="dropdown-item pointer"><a data-edit="fontSize 7"><font size="7">Font7</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 6"><font size="6">Font6</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 5"><font size="5">Font5</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 4"><font size="4">Font4</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 3"><font size="3">Font3</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 2"><font size="2">Font2</font></a></li>\
			<li class="dropdown-item pointer"><a data-edit="fontSize 1"><font size="1">Font1</font></a></li>\
		</ul>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn" title="文本颜色" onclick="$(this).next().click()">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path fill-opacity=".36" d="M0 15h18v3H0z"/><path d="M10 1H8L3.5 13h2l1.12-3h4.75l1.12 3h2L10 1zM7.38 8L9 3.67 10.62 8H7.38z"/>\
			</svg>\
		</a>\
		<input data-edit="foreColor"  type="color" class="d-none">\
		<a class="btn" title="文本背景色" onclick="$(this).next().click()">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path fill-opacity=".36" d="M0 15h18v3H0z"/><path d="M14.5 8.87S13 10.49 13 11.49c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5c0-.99-1.5-2.62-1.5-2.62zm-1.79-2.08L5.91 0 4.85 1.06l1.59 1.59-4.15 4.14c-.39.39-.39 1.02 0 1.41l4.5 4.5c.2.2.45.3.71.3s.51-.1.71-.29l4.5-4.5c.39-.39.39-1.03 0-1.42zM4.21 7L7.5 3.71 10.79 7H4.21z"/>\
			</svg>\
		</a>\
		<input data-edit="hiliteColor"  type="color" class="d-none">\
		<a class="btn" title="撤销格式" data-edit="removeFormat">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M14 11c0-3.33-5-9-5-9s-.85.97-1.85 2.33l6.83 6.83L14 11zM3.55 3.27L2.27 4.55l2.89 2.89C4.49 8.69 4 9.96 4 11c0 2.76 2.24 5 5 5 1.31 0 2.49-.52 3.39-1.34L14.73 17 16 15.73 3.55 3.27z"/>\
			</svg>\
		</a>\
		<a class="btn" title="段落" data-edit="formatBlock P">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M13 3H6v18h4v-6h3c3.31 0 6-2.69 6-6s-2.69-6-6-6zm.2 8H10V7h3.2c1.1 0 2 .9 2 2s-.9 2-2 2z"/>\
			</svg>\
		</a>\
		<a class="btn" title="引用" data-edit="formatBlock BLOCKQUOTE">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M10 5v5h2.75L11 13h2.25L15 10V5h-5zm-7 5h2.75L4 13h2.25L8 10V5H3v5z"/>\
			</svg>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn dropdown-toggle" data-toggle="dropdown" title="标题">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M5 4v3h5.5v12h3V7H19V4z"/>\
			</svg>\
		</a>\
		<div class="dropdown-menu">\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H1"><H1>H1<H1></a></li>\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H2"><H2>H2<H2></a></li>\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H3"><H3>H3<H3></a></li>\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H4"><H4>H4<H4></a></li>\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H5"><H5>H5<H5></a></li>\
			<li class="dropdown-item pointer"><a data-edit="formatBlock H6"><H6>H6<H6></a></li>\
		</div>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn dropdown-toggle" data-toggle="dropdown" title="插入图片">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M16 1H2c-.55 0-1 .45-1 1v14c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V2c0-.55-.45-1-1-1zM3.5 13l2.75-3.54 1.96 2.36 2.75-3.54L14.5 13h-11z"/>\
			</svg>\
		</a>\
		<div class="dropdown-menu">\
			<a class="dropdown-item pointer" data-edit-image="mw-100 w-100 d-block">单行</a>\
			<a class="dropdown-item pointer" data-edit-image="mw-100 d-inline">行内</a>\
			<a class="dropdown-item pointer" data-edit-image="mw-100 float-left pr-3 pb-2">左浮动</a>\
			<a class="dropdown-item pointer" data-edit-image="mw-100 float-right pl-3 pb-2">右浮动</a>\
		</div>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn dropdown-toggle" data-toggle="dropdown" title="插入视频">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">\
				<path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>\
			</svg>\
		</a>\
		<div class="dropdown-menu">\
			<a class="dropdown-item pointer" data-edit-video="mw-100 w-100 d-block">单行</a>\
			<a class="dropdown-item pointer" data-edit-video="mw-100 float-left pr-3 pb-2">左浮动</a>\
			<a class="dropdown-item pointer" data-edit-video="mw-100 float-right pl-3 pb-2">右浮动</a>\
		</div>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn" data-edit="bold" title="粗体 (Ctrl/Cmd+B)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M11.63 7.82C12.46 7.24 13 6.38 13 5.5 13 3.57 11.43 2 9.5 2H4v12h6.25c1.79 0 3.25-1.46 3.25-3.25 0-1.3-.77-2.41-1.87-2.93zM6.5 4h2.75c.83 0 1.5.67 1.5 1.5S10.08 7 9.25 7H6.5V4zm3.25 8H6.5V9h3.25c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="italic" title="斜体 (Ctrl/Cmd+I)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M7 2v2h2.58l-3.66 8H3v2h8v-2H8.42l3.66-8H15V2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="strikethrough" title="删除线">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M8 15h2v-4H8v4zM4 2v2h4v3h2V4h4V2H4zm-1 8h12V8H3v2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="underline" title="下划线 (Ctrl/Cmd+U)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M9 13c2.76 0 5-2.24 5-5V1h-2.5v7c0 1.38-1.12 2.5-2.5 2.5S6.5 9.38 6.5 8V1H4v7c0 2.76 2.24 5 5 5zm-6 2v2h12v-2H3z"/>\
			</svg>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn" data-edit="insertunorderedlist" title="无序列表">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M7 10h9V8H7v2zm0-7v2h9V3H7zm0 12h9v-2H7v2zm-4-5h2V8H3v2zm0-7v2h2V3H3zm0 12h2v-2H3v2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="insertorderedlist" title="有序列表">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M2 13h2v.5H3v1h1v.5H2v1h3v-4H2v1zm0-5h1.8L2 10.1v.9h3v-1H3.2L5 7.9V7H2v1zm1-2h1V2H2v1h1v3zm4-3v2h9V3H7zm0 12h9v-2H7v2zm0-5h9V8H7v2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="outdent" title="取消缩进 (Shift+Tab)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M8 12h8v-2H8v2zm0-4h8V6H8v2zm8 6H2v2h14v-2zM2 9l3.5 3.5v-7L2 9zm0-7v2h14V2H2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="indent" title="缩进 (Tab)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M8 12h8v-2H8v2zM5.5 9L2 5.5v7L5.5 9zM2 16h14v-2H2v2zM2 2v2h14V2H2zm6 6h8V6H8v2z"/>\
			</svg>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<a class="btn" data-edit="justifyleft" title="左对齐 (Ctrl/Cmd+L)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M2 16h10v-2H2v2zM12 6H2v2h10V6zM2 2v2h14V2H2zm0 10h14v-2H2v2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="justifycenter" title="剧中 (Ctrl/Cmd+E)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M4 14v2h10v-2H4zm0-8v2h10V6H4zm-2 6h14v-2H2v2zM2 2v2h14V2H2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="justifyright" title="右对齐 (Ctrl/Cmd+R)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M6 16h10v-2H6v2zm-4-4h14v-2H2v2zM2 2v2h14V2H2zm4 6h10V6H6v2z"/>\
			</svg>\
		</a>\
		<a class="btn" data-edit="justifyfull" title="两边对齐 (Ctrl/Cmd+J)">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M2 16h14v-2H2v2zm0-4h14v-2H2v2zM2 2v2h14V2H2zm0 6h14V6H2v2z"/>\
			</svg>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm border border-info mb-1">\
		<a class="btn" data-edit="insertHorizontalRule" title="分隔线">\
			<b>—</b>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
		<button class="bg-white btn dropdown-toggle" data-toggle="dropdown" title="超链接">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">\
				<path d="M1.9 9c0-1.16.94-2.1 2.1-2.1h4V5H4C1.79 5 0 6.79 0 9s1.79 4 4 4h4v-1.9H4c-1.16 0-2.1-.94-2.1-2.1zM14 5h-4v1.9h4c1.16 0 2.1.94 2.1 2.1 0 1.16-.94 2.1-2.1 2.1h-4V13h4c2.21 0 4-1.79 4-4s-1.79-4-4-4zm-8 5h6V8H6v2z"/>\
			</svg>\
		</button>\
		<div class="dropdown-menu">\
			<div class="input-group">\
				<input class="form-control" placeholder="URL" type="text" data-edit="createLink"/>\
				<button class="btn btn-primary" type="button">Add</button>\
			</div>\
		</div>\
		<a class="btn" data-edit="unlink" title="取消超链接">\
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">\
				<path fill="none" d="M0 0h24v24H0V0z"/>\
				<path d="M17 7h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1 0 1.43-.98 2.63-2.31 2.98l1.46 1.46C20.88 15.61 22 13.95 22 12c0-2.76-2.24-5-5-5zm-1 4h-2.19l2 2H16zM2 4.27l3.11 3.11C3.29 8.12 2 9.91 2 12c0 2.76 2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1 0-1.59 1.21-2.9 2.76-3.07L8.73 11H8v2h2.73L13 15.27V17h1.73l4.01 4L20 19.74 3.27 3 2 4.27z"/>\
				<path fill="none" d="M0 24V0"/>\
			</svg>\
		</a>\
		</div>\
		<div class="bg-white btn-group btn-group-sm mr-2 border border-info mb-1">\
			<button class="bg-white btn dropdown-toggle" data-toggle="dropdown" title="设备">\
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">\
					<path d="M4 6h18V4H4c-1.1 0-2 .9-2 2v11H0v3h14v-3H4V6zm19 2h-6c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h6c.55 0 1-.45 1-1V9c0-.55-.45-1-1-1zm-1 9h-4v-7h4v7z"/>\
				</svg>\
			</button>\
			<div class="dropdown-menu">\
				<a class="dropdown-item pointer" data-edit-device="">自动</a>\
				<a class="dropdown-item pointer" data-edit-device="100%">全屏</a>\
				<a class="dropdown-item pointer" data-edit-device="360px">Galaxy S5</a>\
				<a class="dropdown-item pointer" data-edit-device="411px">Pixel 2</a>\
				<a class="dropdown-item pointer" data-edit-device="375px">iPhone X</a>\
				<a class="dropdown-item pointer" data-edit-device="414px">iPhone 6/7/8 Plus</a>\
				<a class="dropdown-item pointer" data-edit-device="320px">iPhone 5/SE</a>\
				<a class="dropdown-item pointer" data-edit-device="768px">iPad</a>\
				<a class="dropdown-item pointer" data-edit-device="1024px">iPad (横屏)</a>\
				<a class="dropdown-item pointer" data-edit-device="1024">iPad Pro</a>\
				<a class="dropdown-item pointer" data-edit-device="1366">iPad Pro (横屏)</a>\
			</div>\
		</div>\
		',
		toolbarSelector: '[data-role=editor-toolbar]',
		commandRole: 'edit',
		activeToolbarClass: 'btn-info',
		selectionMarker: 'edit-focus-marker',
		selectionColor: 'darkgrey',
		fileUploadUrl:'',
		dragAndDropImages: false,
		fileUploadError: function (reason, detail) { console.log("File upload error", reason, detail); }
	};
}(window.jQuery));