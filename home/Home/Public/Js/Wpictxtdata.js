var moddata = [{
		id:1,
		type:"系统模板",
		list:[
			{id:0,type:"标题",list:[]},
			{id:1,type:"卡片",list:[]},
			{id:2,type:"图文",list:[]},
			{id:3,type:"顶关注",list:[]},
			{id:4,type:"底提示",list:[]},
			{id:5,type:"标题卡片",list:[]},
			{id:6,type:"其他",list:[]}
		]
	},{
		id:2,
		type:"我的模板",
		list:[{
			id:"",
			type:"",
			list:[]
		}]
	}];
var urlTitle = "temp/title/";
var urlImg = "temp/img/";
var urlCard = "temp/card/";
var urlTitlecard = "temp/titlecard/";
var urlTop = "temp/top/";
var urlBottom = "temp/bottom/";
var urlVisitingcard = "temp/visitingcard/";
var urlOther = "temp/other/";
var tempTitleList = [{
						url:urlTitle+"h1.html",
						text:[{
							text:'点击模板会插入到最后或者将模板拖动到想要插入的位置',
							edit:{'fontSize':"24px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'backgroundColor':"",'backgroundImage':"",'textAlign':""}
						}]
					},{
						url:urlTitle+"h2.html",
						text:[{
							text:'点击模板会插入到最后或者将模板拖动到想要插入的位置',
							edit:{'fontSize':"18px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'backgroundColor':"",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h3.html",
						text:[{
							text:'请在这里输入正文内容',
							edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'backgroundColor':"",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h.html",
						text:[{
							text:'请在这里输入正文内容',
							edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':"",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h4.html",
						text:[{
							text:'请在这里输入正文内容',
							edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h5.html",
						text:[{
							text:'请输入标题',
							edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h6.html",
						text:[{
							text:'请输入标题',
							edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h7.html",
						text:[{
							text:'请输入标题',
							edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h8.html",
						text:[{
							text:'请输入标题',
							edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor",'backgroundImage':""}
						}]
					},{
						url:urlTitle+"h9.html",
						text:[{
							text:'第一行标题<br>第二行标题',
							edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor"}
						}]
					},{
						url:urlTitle+"h10.html",
						text:[{
							text:'请输入标题',
							edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':"defaultColor"}
						}]
					}];
var tempCardList = [{
				url:urlCard+"card1.html",
				text:[{
					text:'请',
					edit:{'fontSize':"2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor",'textAlign':'left'}
				},{
					text:'输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'left'}
				}]
			},{
				url:urlCard+"card2.html",
				text:[{
					text:'友情提示：大家使用旺财平台建议浏览器为Chrome，360浏览器、猎豹、遨游、QQ浏览器请切换至急速模式，但是目前还在使用IE的用户，请您尽快更新浏览器，目前微信也只支持Chrome了。',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"italic",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'left'}
				}]
			},{
				url:urlCard+"card4.html",
				text:[{
					text:'&uarr;',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字请输入文字',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'left'}
				}]
			},{
				url:urlCard+"card5.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card6.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card7.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				}]
			},{
				url:urlCard+"card8.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card9.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card10.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card11.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				}]
			},{
				url:urlCard+"card12.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card13.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card14.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card15.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card16.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card17.html",
				text:[{
					text:'<p>分隔符</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.5em",'textDecoration':"",'color': "#333",'backgroundColor':'#f7f7f7','textAlign':'left'}
				}]
			},{
				url:urlCard+"card18.html",
				text:[{
					text:'<p>小贴士</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.5em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card19.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.5em",'textDecoration':"",'color': "#333",'backgroundColor':'#f4f4f4','textAlign':'left'}
				}]
			},{
				url:urlCard+"card20.html",
				text:[{
					text:'<p>标题</p>',
					edit:{'fontSize':"18px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor",'backgroundColor':'#fff','textAlign':'left'}
				},{
					text:'<p>内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card21.html",
				text:[{
					text:'<p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'right'}
				}]
			},{
				url:urlCard+"card22.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'left'}
				}]
			},{
				url:urlCard+"card23.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#fff','textAlign':'center'}
				}]
			},{
				url:urlCard+"card24.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'center'}
				},{
					text:'1',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'textDecoration':"",'color': "#fff",'textAlign':'center'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'center'}
				}]
			},{
				url:urlCard+"card25.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'center'}
				},{
					text:'2',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'textDecoration':"",'color': "#fff",'textAlign':'center'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'center'}
				}]
			},{
				url:urlCard+"card26.html",
				text:[{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'center'}
				},{
					text:'3',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"bold",'textDecoration':"",'color': "#fff",'textAlign':'center'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'center'}
				}]
			}]
var tempImgList = [{
				url:urlImg+"img1.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/8/428.jpg'
	  			}]
			},{
				url:urlImg+"img2.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/8/417.jpg'
	  			}]
			},{
				url:urlImg+"img3.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/8/415.jpg'
	  			}]
			},{
				url:urlImg+"img4.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/8/392.jpg'
	  			}]
			},{
				url:urlImg+"img5.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			}]
			},{
				url:urlImg+"img6.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/410.jpg'
	  			}]
			},{
				url:urlImg+"img7.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/410.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/377.jpg'
	  			}]
			},{
				url:urlImg+"img5-1.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			}]
			},{
				url:urlImg+"img6-1.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/410.jpg'
	  			}]
			},{
				url:urlImg+"img7-1.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/420.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/410.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/9/377.jpg'
	  			}]
			},{
				url:urlImg+"img8.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/5/068.jpg'
	  			}],
				text:[{
					text:'<p>多乐互动</p><p>Duo Le Hu Dong</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				},{
					text:'点击外边图片换背景',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				}]
			},{
				url:urlImg+"img9.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/5/067.jpg'
	  			}],
				text:[{
					text:'<p>多乐互动</p><p>Duo Le Hu Dong</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				},{
					text:'点击外边图片换背景',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				}]
			},{
				url:urlImg+"img10.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/10/77.jpg'
	  			}],
				text:[{
					text:'<p>文字在图左侧</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor"}
				},{
					text:'<p>详细描述</p><p>Y(^_^)Y</p>',
					edit:{'fontSize':"0.6em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor"}
				}]
			},{
				url:urlImg+"img11.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/10/68.jpg'
	  			}],
				text:[{
					text:'<p>文字在图右侧</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor"}
				},{
					text:'<p>详细描述</p><p>Y(^_^)Y</p>',
					edit:{'fontSize':"0.6em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor"}
				}]
			},{
				url:urlImg+"img12.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/10/71.jpg'
	  			}],
				text:[{
					text:'<p>文字标题</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				},{
					text:'<p>可以分别改背景色哦Y(^_^)Y</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff"}
				}]
			},{
				url:urlImg+"img13.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/10/72.jpg'
	  			}],
				text:[{
					text:'<p>文字标题</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'textAlign':'left'}
				},{
					text:'<p>可以分别改背景色哦Y(^_^)Y</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'textAlign':'left'}
				}]
			},{
				url:urlImg+"img14.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/10/70.jpg'
	  			}],
				text:[{
					text:'<p>文字标题</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'text-align':''}
				},{
					text:'<p>可以分别改背景色哦Y(^_^)Y</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'textAlign':''}
				}]
			},{
				url:urlImg+"img15.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/345.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/342.jpg'
	  			}],
				text:[{
					text:'<p>这里可以放一段描述</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "defaultColor",'textAlign':'left'}
				}]
			},{
				url:urlImg+"img16.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/362.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/353.jpg'
	  			}],
				text:[{
					text:'<p>左右分割的图文,您可以放多个组合成一个列表</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'textAlign':'left'}
				},{
					text:'<p>左右分割的图文,您可以放多个组合成一个列表</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'textAlign':'left'}
				}]
			},{
				url:urlImg+"img17.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/5/141.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/11/533.jpg'
	  			}],
				text:[{
					text:'<p>&uarr;</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'textAlign':'center','backgroundColor':'defaultColor'}
				},{
					text:'<p>上边的图文</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'textAlign':'left'}
				},{
					text:'<p>&rarr;</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'textAlign':'center','backgroundColor':'defaultColor'}
				},{
					text:'<p>右边的图文</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'textAlign':'left'}
				}]
			},{
				url:urlImg+"img18.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/5/036.jpg'
	  			}],
				text:[{
					text:'<p>杂志封面</p>',
					edit:{'fontSize':"1.5em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'center'}
				},{
					text:'<p>Love&nbsp;&hearts;</p>',
					edit:{'fontSize':"1.25em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#999",'textAlign':'center'}
				},{
					text:'<p>点击外边图片双击更换背景</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "rgb(31, 73, 125)",'textAlign':'center'}
				},{
					text:'<p>(☆_☆)</p>',
					edit:{'fontSize':"0.8em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'textAlign':'center'}
				}]
			},{
				url:urlImg+"img19.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/5/045.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/362.jpg'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/13/353.jpg'
	  			}]
			},{
				url:urlImg+"img20.html",
				img:[{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/6/20.png'
	  			},{
					img:'http://static.wangcaio2o.com/Home/Upload/piclist/6/22.png'
	  			}],
				text:[{
					text:'<p>圣诞老人的图片会挡掉雪人的图片</p>',
					edit:{'fontSize':"0.75em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'left'}
				},{
					text:'<p>Duang～</p>',
					edit:{'fontSize':"1.25em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#666",'textAlign':'right'}
				},{
					text:'<p>可以试试用PNG图片会更生动</p>',
					edit:{'fontSize':"12px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#999",'textAlign':'right'}
				}]
			}]
var tempTopList = [
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/1.png'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/2.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/3.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/4.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/8.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/9.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/10.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/11.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/12.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/13.png'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/14.png'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/15.png'}]},
			//{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/16.png'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/17.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/18.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/19.png'}]},
			//{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/20.png'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/21.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/22.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/23.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/24.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/25.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/26.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/27.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/28.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/29.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/30.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/31.jpg'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/32.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/33.gif'}]},
			{url:urlTop+"top2.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/34.gif'}],text:[{text:'点击上面的蓝字关注我们哦！',edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#333"}}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/35.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/36.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/37.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/38.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/39.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/40.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/41.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/42.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/43.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/44.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/45.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/46.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/47.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/48.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/49.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/50.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/51.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/52.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/53.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/54.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/55.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/56.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/57.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/58.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/59.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/60.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/61.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/62.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/63.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/64.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/65.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/66.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/67.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/68.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/69.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/70.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/71.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/72.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/73.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/74.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/75.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/76.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/77.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/78.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/79.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/80.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/81.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/82.gif'}]},
			{url:urlTop+"top1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/83.gif'}]},
			];
var tempBottomList = [
			{url:urlBottom+"bottom3.html",text:[{text:'点击“阅读原文”',edit:{'fontSize':"1.25em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.5em",'textDecoration':"",'color': "defaultColor"}}]},
			{url:urlBottom+"bottom4.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/3.gif'}],text:[{text:'点击下方阅读原文',edit:{'fontSize':"1.25em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.5em",'textDecoration':"",'color': "defaultColor"}}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/1.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/16.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/21.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/22.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/23.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/24.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/25.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/26.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/27.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/28.png'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/29.png'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/30.png'}]},
			{url:urlBottom+"bottom2.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/31.gif'}],text:[{text:'点击阅读全文了解更多哦！',edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#333"}}]},
			{url:urlBottom+"bottom2.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/32.gif'}]},
			{url:urlBottom+"bottom2.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/33.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/34.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/35.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/36.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/37.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/38.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/39.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/40.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/41.jpg'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/42.png'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/43.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/44.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/45.jpg'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/46.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/47.jpg'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/48.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/49.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/50.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/51.gif'}]},
			{url:urlBottom+"bottom1.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/bottomTip/52.gif'}]}];
var tempVisitingcardList = [{
				url:urlVisitingcard+"visit1.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#eee','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit2.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'#bbb','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#eee','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit3.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#eee','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit4.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"1em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'none','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit5.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'left'}
				},{
					text:'<p>请输入文字,请输入文字,请输入文字,请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit6.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'none','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit7.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'none','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit8.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "defaultColor",'textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'#f7f7f7','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit9.html",
				text:[{
					text:'<p>标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'center'}
				}]
			},{
				url:urlVisitingcard+"visit10.html",
				text:[{
					text:'<p>小标注</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'left'}
				},{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"16px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#666",'backgroundColor':'none','textAlign':'left'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#666",'backgroundColor':'none','textAlign':'left'}
				}]
			},{
				url:urlVisitingcard+"visit11.html",
				text:[{
					text:'<p>请输入标题</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"2.5em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor','textAlign':'center'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'none','textAlign':'left'}
				}]
			}];
var tempOtherList = [{
				url:urlOther+"other1.html",
				text:[{
					text:'<p>请输入公众号名称</p>',
					edit:{'fontSize':"24px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				},{
					text:'<p>微信号：请输入微信号</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				},{
					text:'<p>请输入文字</p><p>请输入文字</p><p>请输入文字</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'none','textAlign':'center'}
				}]
			},{
				url:urlOther+"other2.html",
				img:[{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/002-conv-1-left-2-v2-img1.png'
	  			},{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/002-conv-1-left-2-v2-img0.png'
	  			}],
				text:[{
					text:'<p>请输入</p>',
					edit:{'fontSize':"12px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				},{
					text:'<p>请输入内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'rgb(255, 228, 200)'}
				}]
			},{
				url:urlOther+"other3.html",
				img:[{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/002-conv-1-left-2-v2-img1.png'
	  			}],
				text:[{
					text:'<p>请输入</p>',
					edit:{'fontSize':"12px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333",'textAlign':'left'}
				},{
					text:'<p>请输入内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				}]
			},{
				url:urlOther+"other4.html",
				img:[{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/002-conv-1-left-2-v2-img1.png'
	  			},{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/003-conv-1-right-2-v2-img0.png'
	  			}],
				text:[{
					text:'<p>请输入</p>',
					edit:{'fontSize':"12px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				},{
					text:'<p>请输入内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1.25em",'textDecoration':"",'color': "#333",'backgroundColor':'rgb(255, 228, 200)'}
				}]
			},{
				url:urlOther+"other5.html",
				img:[{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/002-conv-1-left-2-v2-img1.png'
	  			}],
				text:[{
					text:'<p>请输入</p>',
					edit:{'fontSize':"12px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				},{
					text:'<p>请输入内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"1em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				}]
			},{
				url:urlOther+"other6.html",
				img:[{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/006-activity-1-v1-img0.png'
	  			},{
					img:'http://statics.xiumi.us/stc/images/templates-assets/parts/701-other/006-activity-1-v1-img1.png'
	  			}],
				text:[{
					text:'<p>请输入活动名称</p>',
					edit:{'fontSize':"18px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'lineHeight':"2em",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				},{
					text:'<p>请输入活动时间</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				},{
					text:'<p>请输入活动地点</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				},{
					text:'<p>活动介绍</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#fff",'backgroundColor':'defaultColor'}
				},{
					text:'<p>请输入内容</p><p>请输入内容</p><p>请输入内容</p><p>请输入内容</p>',
					edit:{'fontSize':"14px",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				}]
			},
				/*----------------------分享-------------------------*/
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-2.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-3.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-4.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-5.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-6.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-7.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-8.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-9.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-10.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-11.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-12.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-13.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-14.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-15.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-16.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-17.png'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-18.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-19.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/share-52.gif'}]},
				/*----------------------小符号-------------------------*/
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-1.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-2.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-1.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-6.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-7.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-8.png'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-9.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-10.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-11.png'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-12.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-13.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-14.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-15.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-16.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-17.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-18.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-19.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-20.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-21.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-22.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-24.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-25.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-26.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-27.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-28.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-29.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-30.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-31.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-32.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-33.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-34.png'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-35.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-36.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-37.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-38.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-39.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-40.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-41.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-42.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-43.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-44.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-45.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-46.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-47.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-48.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-49.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-50.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-51.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-52.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-53.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-54.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-55.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-56.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-57.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-59.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-60.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-61.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-62.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-63.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-64.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-66.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-67.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-68.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-79.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-80.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-81.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-82.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-83.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-84.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-85.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-86.gif'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-87.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-88.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-89.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-90.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-91.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-92.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-93.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-94.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-95.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-96.jpg'}]},
				{url:urlOther+"other8.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/icon-97.jpg'}]},
				/*----------------------分割线-------------------------*/
				
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-1.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-2.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-3.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-4.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-5.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-6.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-7.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-8.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-9.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-10.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-11.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-12.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-13.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-14.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-15.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-16.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-17.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-18.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-19.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-20.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-21.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-22.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-23.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-24.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-25.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-26.jpg'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-27.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-28.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-29.gif'}]},
				{url:urlOther+"other7.html",img:[{img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/br-30.gif'}]},
				{url:urlOther+"other10.html"},
				{url:urlOther+"other11.html"},
				{url:urlOther+"other12.html"},
				{url:urlOther+"other13.html"},
				{url:urlOther+"other14.html"},
				{url:urlOther+"other15.html"},
				/*----------------------节日-------------------------*/
				{
				url:urlOther+"other16.html",
				img:[{
					img:'Home/Public/Image/pictxt/FatherDay-2.png'
	  			}],
	  			text:[{
					text:'<p>父亲节来了，要您一分辛劳，还您一分休憩；要您一分苍老，还您一分美丽；要您一分节俭，还您一分潇洒。要您一分烦恼，还您一分如意。父亲节来了，祝您健康快乐，顺心顺意。</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				}]
	
			},{
				url:urlOther+"other17.html",
				img:[{
					img:'Home/Public/Image/pictxt/FatherDay-1.png'
	  			}],
	  			text:[{
					text:'<p>父亲是您光荣的称谓，在您身上闪耀着爱的光辉，您给子女的爱无怨无悔，您工作再苦也不说累。今天是父亲节，祝福全天下的父亲们健康幸福，节日快乐！</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "#333"}
				}]
	
			},{
				url:urlOther+"other18.html",
				img:[{
					img:'Home/Public/Image/pictxt/dwj-1.png'
	  			}],
	  			text:[{
					text:'<p>听，是谁在三千红尘中，轻轻弹奏一曲愁肠的弦音。又是谁，沉醉在烟雨红尘中，墨香袅袅的书写人间的风花雪月，一首唐诗，一阙宋词，一曲箫音，涟漪了前世今生的眷恋。长城诗选依然香如故。诗人们，端午已至，片片绿叶，带着夏雨的清凉；缕缕清风，弥漫田野的芳香；朵朵白云，送来心灵的歌谣；丝丝真情，承载所有的梦想。愿诗人浪漫夏日诗意盎然！</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "rgb(21, 133, 47)"}
				}]
	
			},{
				url:urlOther+"other18.html",
				img:[{
					img:'Home/Public/Image/pictxt/dwj-2.png'
	  			}],
	  			text:[{
					text:'<p>粽子淡淡的清香，在艾草的苦味中飘浮。笛声悠远，麦子饱满，汩罗江边，先生那...端午是竹叶的色彩，端午是艾草的青涩，端午是麦收的季节，端午是屈原的祭日，端午是人们永远的牵挂！</p>',
					edit:{'fontSize':"1.2em",'fontFamily':"",'fontStyle':"normal",'fontWeight':"normal",'textDecoration':"",'color': "rgb(21, 133, 47)"}
				}]
	
			},
				
				/*----------------------二维码-------------------------*/
				{
				url:urlOther+"other9.html",
				img:[{
					img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/code-28.gif'
	  			},{
					img:'http://test.wangcaio2o.com/Home/Public/Image/pictxt/code-1.jpg'
	  			}]
			},
			
			
				



];
moddata[0].list[0].list = tempTitleList;
moddata[0].list[1].list = tempCardList;
moddata[0].list[2].list = tempImgList;
moddata[0].list[3].list = tempTopList;
moddata[0].list[4].list = tempBottomList;
moddata[0].list[5].list = tempVisitingcardList;
moddata[0].list[6].list = tempOtherList;
app.run(["$templateCache",function(a){
		a.put("temp/title/h.html",'<fieldset style="border:0; margin-top: 0; margin-bottom: 0; clear: both" ui-temp="text" ui-style></fieldset>'),
		a.put("temp/img/img.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></fieldset>'),
		a.put("temp/title/h1.html",'<blockquote style="font-size: 2em; font-family: inherit; font-weight: 500;box-sizing: border-box; margin: 0.5em 0; padding: 0; border: none"><h1 style="font-size: 1em" ui-temp="text" ui-style></h1></blockquote>'),
		a.put("temp/title/h2.html",'<blockquote style="box-sizing: border-box; margin: 0.5em 0; padding: 0; border: none;font-size: 1.5em; font-family: inherit; font-weight: 500"><h2 style="font-size: 1em" ui-temp="text" ui-style></h2></blockquote>'),		
		a.put("temp/title/h3.html",'<fieldset style="border:0; margin-top: 0; margin-bottom: 0; clear: both" ui-temp="text" ui-style></fieldset>'),		
		a.put("temp/title/h4.html",'<fieldset style="border:0;text-align: left; margin: 0.5em 0"><span style="display: inline-block; padding: 0.3em 0.5em; border-radius: 0.5em;color: white; text-align: center; font-size: 1em; box-shadow: #a5a5a5 0.2em 0.2em 0.1em;background-color: rgb(0, 112, 192)" ui-temp="text" ui-style ></span></fieldset>'),		
		a.put("temp/title/h5.html",'<fieldset style="border:0;text-align: center; margin:0.5em 0"><span style="display: inline-block; padding: 0.3em 0.5em; border-radius: 0.5em;color: white; text-align: center; font-size: 1em; box-shadow: #a5a5a5 0.2em 0.2em 0.1em;background-color: rgb(0, 112, 192)" ui-temp="text" ui-style ></span></fieldset>'),		
		a.put("temp/title/h6.html",'<fieldset style="border:0;text-align: left; margin:0.5em 0; white-space: nowrap; overflow: hidden; box-sizing: border-box!important"><section style="display: inline-block" ui-temp="text" ui-style><section style="height: 2em; display: inline-block; padding: 0.3em 0.5em; color: white; text-align: center; font-size: 1em; line-height: 1.4;vertical-align: top; box-sizing: border-box!important" ui-text></section><section style="box-sizing: border-box !important; display: inline-block;height: 2em; width: 0.5em; vertical-align: top;border-left: 0.5em solid;border-top: 1em solid transparent !important;border-bottom: 1em solid transparent !important;-moz-border-top-colors: transparent !important;-moz-border-bottom-colors: transparent !important" ng-style="{\'font-size\':value.text[0].edit.fontSize || \'inherit\',\'line-height\':value.text[0].edit.lineHeight || \'inherit\',\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor}">{{value.edit.defaultColor}}</section></section></fieldset>'),
		a.put("temp/title/h7.html","<fieldset style=\"border: 0; margin:0.5em 0; clear: both\"><section style=\"border-top: 2px solid red; text-align: left; padding-top: 3px\" ng-style=\"{'border-top-color':value.text[0].edit.defaultColor || value.defaultColor}\" ui-temp ui-style><section style=\"display: inline-block; padding:0.5em; vertical-align: top\" ui-text></section><section style=\"width: 0; display: inline-block; vertical-align: top;border-left: 0.8em solid #CCCCCC;border-top: 1em solid #CCCCCC;border-right: 0.8em solid transparent !important;border-bottom: 1em solid transparent !important\" ng-style=\"{'font-size':value.text[0].edit.fontSize || 'inherit','line-height':value.text[0].edit.lineHeight || 'inherit','border-left-color':value.text[0].edit.defaultColor || value.defaultColor,'border-top-color':value.text[0].edit.defaultColor || value.defaultColor}\"></section></section></fieldset>"),		
		a.put("temp/title/h8.html","<fieldset style=\"border: 0; margin:0.5em 0; clear: both\"><section style=\"border-top: 2px solid red; text-align: left\" ng-style=\"{'border-top-color':value.text[0].edit.defaultColor || value.defaultColor}\" ui-temp ui-style><section style=\"display: inline-block; padding: 0.5em\" ui-text></section></section></fieldset>"),		
		a.put("temp/title/h9.html","<fieldset style=\"border: 0; margin:0.5em 0; clear: both\"><section style=\"border-bottom: 1px solid black; text-align: left\" ng-style=\"{'border-bottom-color':value.text[0].edit.defaultColor || value.defaultColor}\" ui-temp ui-style><section style=\"display: inline-block; box-sizing: border-box;padding: 0; margin-bottom: -1px; line-height: 1.1;border-bottom: 6px solid red\" ng-style=\"{'border-bottom-color':value.text[0].edit.defaultColor || value.defaultColor}\" ui-text></section></section></fieldset>"),
		a.put("temp/title/h10.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section ui-temp="text" ui-style><section style="height: 0; white-space: nowrap; margin: 0 1em;border-top: 1.5em solid #f96e57;border-bottom: 1.5em solid #f96e57;border-left: 1.5em solid transparent !important;border-right: 1.5em solid transparent !important;" ng-style="{\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-bottom-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="height: 0; white-space: nowrap; margin: -2.75em 1.65em -2.3em;border-top: 1.3em solid #ffffff;border-bottom: 1.3em solid #ffffff;border-left: 1.3em solid transparent;border-right: 1.3em solid transparent"></section><section style="height: 0; white-space: nowrap; margin: 0 2.1em 0;vertical-align: middle;border-top: 1.1em solid #f96e57;border-bottom: 1.1em solid #f96e57;border-left: 1.1em solid transparent !important;border-right: 1.1em solid transparent !important;" ng-style="{\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-bottom-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="max-height: 1em; padding: 0; margin-top: -0.5em; color: white;line-height: 1; white-space: nowrap; overflow: hidden" ui-text></section></section></section></fieldset>'),

		/*----------------------卡片-------------------------*/
		a.put("temp/card/card1.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="float: left; padding-right: 0.1em;" ui-temp ui-style ui-temp-index="0"></section><section style="text-align:left;" ui-temp ui-style ui-temp-index="1"></section></fieldset>'),
		a.put("temp/card/card2.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.7em 0px; border-top:solid 1px #333; border-bottom:solid 1px #333;" ng-style="{\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-bottom-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="text-align:left;" ui-temp ui-style></section></section></fieldset>'),
		a.put("temp/card/card3.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.7em 0px; border-top:solid 1px #333; border-bottom:solid 1px #333;"><section style="text-align:left;" ui-temp ui-style></section></section></fieldset>'),
		a.put("temp/card/card4.html",'<fieldset style="border:0;margin: 1em 0 1em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="float: left; padding: 0px 0.4em; margin: 3px 7px 0px 0px;" ui-temp ui-style ui-temp-index="0"></section><section style="text-align:left;" ui-temp ui-style ui-temp-index="1"></section></fieldset>'),
		a.put("temp/card/card5.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 10px; border: 2px dashed rgb(182, 182, 182);" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card6.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 10px; border: 1px solid rgb(182, 182, 182);" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card7.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="border: 2px dotted white; padding: 10px; border-radius: 8px 64px 48px 8px 8px 16px 0px 48px; box-shadow: rgb(225, 225, 225) 8px 8px 3px;" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card8.html",'<fieldset style="border:0;margin: 1em 0 1em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="border: 1px solid rgb(192, 200, 209); padding: 10px; box-shadow: rgb(170, 170, 170) 0px 0px 10px;" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card9.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="margin:0 2em;padding: 10px; border: 2px dashed rgb(182, 182, 182);" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card10.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="margin:0 2em;padding: 10px; border: 1px solid rgb(182, 182, 182);" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card11.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="margin:0 2em;border: 2px dotted white; padding: 10px; border-radius: 8px 64px 48px 8px 8px 16px 0px 48px; box-shadow: rgb(225, 225, 225) 8px 8px 3px;" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card12.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="margin:0 2em;border: 1px solid rgb(192, 200, 209); padding: 10px; box-shadow: rgb(170, 170, 170) 0px 0px 10px;" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card13.html",'<fieldset style="border:0;margin: 1em 0 1em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 10px; margin: 0px; border: 2px dotted black;" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card14.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; border: 0.1em solid rgb(0, 0, 0); border-radius: 15px;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 10px; margin: 0px; border: 3px solid black;border-radius: 15px;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/card/card15.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 10px; margin: 0px; border: 1px solid black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></fieldset>'),
		a.put("temp/card/card16.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; border: 3px solid rgb(0, 0, 0);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 10px; margin: 0px; border: 1px solid black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/card/card17.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="display: inline-block; font-size: 1em; font-family: inherit; font-style: normal; font-weight: inherit; text-align: center; text-decoration: inherit; color: rgb(95, 156, 239); border-color: rgb(95, 156, 239);"><section style="display: inline-block; vertical-align: top; width: 0px; border-right-width: 0.5em; border-right-style: solid; border-top-width: 1em !important; border-top-style: solid !important; border-top-color: white !important; border-bottom-width: 1em !important; border-bottom-style: solid !important; border-bottom-color: white !important; border-right-color: rgb(95, 156, 239);" ng-style="{\'border-right-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="display: inline-block; vertical-align: top"><span style="float: left; padding: 0.2em 0.3em;" ui-temp ui-style></span></section><section style="display: inline-block; vertical-align: top; width: 0px; border-left-width: 0.5em; border-left-style: solid; border-top-width: 1em !important; border-top-style: solid !important; border-top-color: white !important; border-bottom-width: 1em !important; border-bottom-style: solid !important; border-bottom-color: white !important; border-left-color: rgb(95, 156, 239);" ng-style="{\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/card/card18.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding:5px; border-left: 5px solid rgb(182, 182, 182);" ng-style="{\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card19.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding:4px; margin: 0px; border-left-width: 6px; border-left-style: solid;border-radius: 5px 0px 0px 5px; box-shadow: rgb(153, 153, 153) 0em 0.2em 0.2em;" ng-style="{\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card20.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0px 8px; border-left-width: 3px; border-left-style: solid; font-size: 1.5em; font-family: inherit; font-style: normal; font-weight: inherit; text-align: inherit; text-decoration: inherit; color: rgb(95, 156, 239); border-color: rgb(95, 156, 239); background-color: transparent;" ng-style="{\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="line-height: 1.4; font-family: inherit; font-style: normal;" ui-temp ui-style ui-temp-index="0"></section><section style=" margin-top: 5px; padding-left: 3px;"  ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/card/card21.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 10px; border-right: 5px solid rgb(182, 182, 182);" ng-style="{\'border-right-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp ui-style></section></fieldset>'),
		a.put("temp/card/card22.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section><section style="height: 16px; box-sizing: border-box"><section style="height: 100%; width: 24px; float: left; border-top-width: 6px; border-top-style: solid; border-color: rgb(249, 110, 87); border-left-width: 6px; border-left-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="height: 100%; width: 24px; float: right; border-top-width: 6px; border-top-style: solid; border-color: rgb(249, 110, 87); border-right-width: 6px; border-right-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="display: inline-block; color: transparent; clear: both"></section></section><section style="margin: -13px 3px -20px 3px; padding: 18px; border: 1px solid rgb(249, 110, 87); border-radius: 6px; word-wrap: break-word; box-sizing: border-box;"  ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp="text" ui-style ui-temp-index="0"></section><section style="height: 16px; box-sizing: border-box"><section style="height: 100%; width: 24px; float: left; border-bottom-width: 6px; border-bottom-style: solid; border-color: rgb(249, 110, 87); border-left-width: 6px; border-left-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="height: 100%; width: 24px; float: right; border-bottom-width: 6px; border-bottom-style: solid; border-color: rgb(249, 110, 87); border-right-width: 6px; border-right-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></section></fieldset>'),
		a.put("temp/card/card23.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section><section style="margin: 0px; padding: 32px 16px 16px; white-space: normal; display: block; background-image: repeating-linear-gradient(135deg, transparent, transparent 4px, white 4px, white 12px); background-color: rgb(249, 110, 87);" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="width: 32px; margin: -40px auto; font-size: 64px; line-height: 1; color: rgb(249, 110, 87); background: white;" ng-style="{ \'color\':value.text[0].edit.defaultColor || value.defaultColor}">&quot;</section><section style="margin-top: 0px; padding: 8px;" ui-temp ui-style></section></section></section></fieldset>'),
		a.put("temp/card/card24.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="display: inline-block; border: 6px solid rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="margin: -6px 8px; padding: 8px; border-top: 8px solid white; border-bottom: 8px solid white"><section><section style="display: inline-table; vertical-align: middle"><section style="display: table; vertical-align: middle;" ui-temp ui-style ui-temp-index="0"></section></section><section style="display: inline-block; vertical-align: middle; margin: 0px; height: 2em; width: 2em; box-sizing: border-box; border-radius: 50% 50% 0px;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="height: 1.7em; width: 1.7em; margin: 0.15em; border-radius: 50%; border: 0.15em solid white; box-sizing: border-box"><section style="line-height:1.4em; font-size: 1.4em; font-family: inherit; font-style: normal; color: rgb(255, 255, 255);" ui-temp ui-style ui-temp-index="1"></section></section></section></section><section style="margin: 8px 0px; border-top-width: 1px; border-top-style: solid; border-color: rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section ui-temp ui-style ui-temp-index="2"></section></section></section></fieldset>'),
		a.put("temp/card/card25.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="display: inline-block; border: 6px solid rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="margin: -6px 8px; padding: 8px; border-top: 8px solid white; border-bottom: 8px solid white"><section><section style="display: inline-block; vertical-align: middle; margin: 0px; height: 2em; width: 2em; box-sizing: border-box; border-radius: 50% 50% 0px;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="height: 1.7em; width: 1.7em; margin: 0.15em; border-radius: 50%; border: 0.15em solid white;box-sizing: border-box"><section style="line-height:1.4em; font-size: 1.4em; font-family: inherit; font-style: normal; color: rgb(255, 255, 255);" ui-temp ui-style ui-temp-index="1"></section></section></section><section style="display: inline-table; vertical-align: middle"><section style="display: table; vertical-align: middle;" ui-temp ui-style ui-temp-index="0"></section></section></section><section style="margin: 8px 0px; border-top-width: 1px; border-top-style: solid; border-color: rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section ui-temp ui-style ui-temp-index="2"></section></section></section></fieldset>'),
		a.put("temp/card/card26.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="display: inline-block; border: 6px solid rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="margin: -6px 8px; padding: 8px; border-top: 8px solid white; border-bottom: 8px solid white"><section><section style="display: inline-table; vertical-align: middle"><section style="display: inline-block; vertical-align: middle; margin: 0px; height: 2em; width: 2em; box-sizing: border-box; border-radius: 50%;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="height: 1.7em; width: 1.7em; margin: 0.15em; border-radius: 50%; border: 0.15em solid white;box-sizing: border-box"><section style="line-height:1.4em; font-size: 1.4em; font-family: inherit; font-style: normal; color: rgb(255, 255, 255);" ui-temp ui-style ui-temp-index="1"></section></section></section><section style="display: table; vertical-align: middle;" ui-temp ui-style ui-temp-index="0"></section></section></section><section style="margin: 8px 0px; border-top-width: 1px; border-top-style: solid; border-color: rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section ui-temp ui-style ui-temp-index="2"></section></section></section></fieldset>'),



		/*----------------------图片-------------------------*/

		a.put("temp/img/img1.html",'<fieldset style="border: none; margin:0"><section style="width:100%; line-height:0;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></section></fieldset>'),
		a.put("temp/img/img2.html",'<fieldset style="border: none; margin:0"><section style="width:100%; line-height:0; margin:0.5em 0;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></section></fieldset>'),
		a.put("temp/img/img3.html",'<fieldset style="border: none; margin:0"><section style="width:100%; line-height:0; margin:0.5em 0;box-shadow: 0.2em 0.2em 0.5em rgba(0,0,0,.7);"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></section></fieldset>'),
		a.put("temp/img/img4.html",'<fieldset style="border: none; margin:0"><section style="width:100%; line-height:0; margin:0.5em 0;border: 0.3em solid white;box-shadow: 0.1em 0.1em 0.3em rgba(0,0,0,.7);box-sizing: border-box;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></section></fieldset>'),
		a.put("temp/img/img5.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="width:10em;height:10em; line-height:0; margin:0 auto;border-radius: 50%;" ui-temp="img" data-backimg></section></fieldset>'),
		a.put("temp/img/img6.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="width:9em;height:9em; line-height:0; margin:0 0.2em;border-radius: 50%; display:inline-block;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="width:9em;height:9em; line-height:0; margin:0 0.2em;border-radius: 50%; display:inline-block;" ui-temp="img" data-backimg ui-temp-index="1"></section></fieldset>'),
		a.put("temp/img/img7.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="width:6em;height:6em; line-height:0; margin:0 0.2em;border-radius: 50%; display:inline-block;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="width:6em;height:6em; line-height:0; margin:0 0.2em;border-radius: 50%; display:inline-block;" ui-temp="img" data-backimg ui-temp-index="1"></section><section style="width:6em;height:6em; line-height:0; margin:0 0.2em;border-radius: 50%; display:inline-block;" ui-temp="img" data-backimg ui-temp-index="2"></section></fieldset>'),
		a.put("temp/img/img5-1.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-shadow:0 0 10px rgba(159, 160, 160, 0.5);  padding: 10px;width: 10em; height:10em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 7px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="clear: both"></section></section></section></fieldset>'),
		a.put("temp/img/img6-1.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-shadow:0 0 10px rgba(159, 160, 160, 0.5); padding: 10px;width: 8em; height:8em;margin:0 0.4em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 7px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="clear: both"></section></section></section><section style="box-shadow:0 0 10px rgba(159, 160, 160, 0.5); padding: 10px;width: 8em; height:8em;margin:0 0.4em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 7px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="1"></section><section style="clear: both"></section></section></section></fieldset>'),
		a.put("temp/img/img7-1.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-shadow:0 0 6px rgba(159, 160, 160, 0.5); padding: 6px;width: 5em; height:5em;margin:0 0.4em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 5px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="clear: both"></section></section></section><section style="box-shadow:0 0 6px rgba(159, 160, 160, 0.5); padding: 6px;width: 5em; height:5em;margin:0 0.4em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 5px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="1"></section><section style="clear: both"></section></section></section><section style="box-shadow:0 0 6px rgba(159, 160, 160, 0.5); padding: 6px;width: 5em; height:5em;margin:0 0.4em; display: inline-block;vertical-align: top"><section style="width:100%;height:100%;box-sizing: border-box;box-shadow:inset 0 0 10px rgba(0, 0, 0, 0.29);  padding: 5px"><section style="width:100%;height:100%;" ui-temp="img" data-backimg ui-temp-index="2"></section><section style="clear: both"></section></section></section></fieldset>'),
		a.put("temp/img/img8.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="position: relative; z-index:1; width: 12em;height: 12em;float: left;padding: 2.8em 1.5em 2em 3.5em;margin-top: -9em;margin-left: 0em;border-radius: 50%;box-sizing: border-box;text-align: left;background-color: rgba(214, 233, 202, 0.8);"><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px; border-top: 1px  solid #fff; border-bottom: 3px  solid #fff;" ui-temp ui-style ui-temp-index="0"></section><section style="padding: 0.5em 0px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img9.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="position: relative; z-index:1; width: 12em;height: 12em;float: right;padding: 2.8em 1.5em 2em 3.5em;margin-top: -9em;margin-left: 0em;border-radius: 50%;box-sizing: border-box;text-align: left;background-color: rgba(237, 209, 216,0.85);"><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px; border-top: 1px  solid #fff; border-bottom: 3px  solid #fff;" ui-temp ui-style ui-temp-index="0"></section><section style="padding: 0.5em 0px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img10.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-sizing: border-box; width: 65%; float: right; padding: 0 0 0 0.1em"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img"></section><section style="display: inline-block; width: 35%; box-sizing: border-box; float: right; padding: 0 0.1em 0 0; text-align: right"><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px; color: rgb(52, 54, 60); border-bottom-width: 1px; border-bottom-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp="img" ui-style ui-temp-index="0"></section><section style="box-sizing: border-box; margin-right: 0.3em; padding: 3px 5px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img11.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-sizing: border-box; width: 65%; float: left; padding: 0 0 0 0.1em"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img"></section><section style="display: inline-block; width: 35%; box-sizing: border-box; float: left; padding: 0 0 0 0.1em; text-align: left"><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px; color: rgb(52, 54, 60); border-bottom-width: 1px; border-bottom-style: solid;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp="img" ui-style ui-temp-index="0"></section><section style="box-sizing: border-box; margin-right: 0.3em; padding: 3px 5px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img12.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="position: relative; z-index:1; float:right; padding: 0.1em 0.5em; margin: -2em 0px 0px;box-sizing: border-box; text-align:right;opacity:0.9;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section><section style="width:10em;padding: 0.5em 0px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img13.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="position: relative; z-index:1; float:left; padding: 0.1em 0.5em; margin: -2em 0px 0px;box-sizing: border-box; text-align:left;opacity:0.9;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section><section style="width:10em;padding: 0.5em 0px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img14.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="position: relative; z-index:1;display: inline-block; padding: 0.1em 0.5em; margin: -2em auto 0px auto;box-sizing: border-box; text-align:center;opacity:0.9;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section><section style="width:10em;padding: 0.5em 0px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img15.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img" ui-temp-index="0"><section style="width:8em;height:8em; border:solid 2px #fff; box-sizing: border-box;float: right;margin-top: -2em; margin-right: 1em;-webkit-transform: rotate3d(0, 0, 1, 15deg);transform: rotate3d(0, 0, 1, 15deg); opacity: 0.99" ui-temp="img" data-backimg ui-temp-index="1"></section><section style="box-sizing: border-box; margin: 0.5em 6em 0.5em 0px;" ui-temp ui-style></section><section style="display:block; clear: both">&nbsp;</section></fieldset>'),
		a.put("temp/img/img16.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-sizing: border-box;width: 50%;float: left;padding: 0 0.1em 0 0"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img" ui-temp-index="0"><section style="box-sizing: border-box; margin: 0.3em 0px 1em 0.3em;" ui-temp ui-style ui-temp-index="0"></section></section><section style="box-sizing: border-box;width: 50%;float: right;padding: 0 0 0 0.1em"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img" ui-temp-index="1"><section style="box-sizing: border-box; margin: 0.3em 0px 1em 0.3em;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/img/img17.html",'<fieldset style="border: none; margin:0; text-align:center;"><img style="box-sizing: border-box; width: 100%; height: auto !important; text-align: start" ui-temp="img" ui-temp-index="0"><section style="box-sizing: border-box;width: 50%;float: left;border: 0;padding: 0.5em;text-align: left;"><section style="display: inline-block; width: 1.2em; height: 1.2em; float: left; padding: 0px 0.1em; margin: 3px 7px 0px 0px;" ui-temp ui-style ui-temp-index="0"></section><section style=" display:inline-block;box-sizing: border-box;" ui-temp ui-style ui-temp-index="1"></section></section><img style="width: 50%;float: right;box-sizing: border-box; text-align: start" ui-temp="img" ui-temp-index="1"><section style="box-sizing: border-box; width: 50%;float: left; border: 0;padding: 0.5em"><section style="display: inline-block; width: 1.2em; height: 1.2em; float: left; padding: 0px 0.1em; margin: 3px 7px 0px 0px;margin-top:3px;" ui-temp ui-style ui-temp-index="2"></section><section ui-temp ui-style ui-temp-index="3"></section></section></fieldset>'),
		a.put("temp/img/img18.html",'<fieldset style="border: none; margin:0; text-align:center;"><section style="box-sizing: border-box; width: 100%; height: 30em;" ui-temp="img" data-backimg></section><section style="margin: -23em auto 8em; box-sizing: border-box; width: 15em; height: 15em; border-radius: 50%; border: 0.2em solid white; opacity:0.95"><section style="margin: 0.333em; box-sizing: border-box; line-height: 1; width: 14em; height: 14em; border-radius: 50%; text-align: center; padding-top: 3em; background-color: white;"><section style="width: 8em;margin: 0px auto;" ui-temp ui-style ui-temp-index="0"></section><section style="width: 90%; margin: 10px auto 0px; vertical-align: middle;"><section style="display: inline-block; margin: 6px 0px; width: 1.6em; height: 0.4em;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="display: inline-block; width: 4.5em; line-height: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;" ui-temp ui-style ui-temp-index="1"></section><section style="display: inline-block; margin: 6px 0px; width: 1.6em; height: 0.4em;" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section><section style="width: 14em; height: 1.2em; margin: 1em auto 0px;" ui-temp ui-style ui-temp-index="2"></section><section style="width: 14em; height: 1.2em; margin: 1em auto 0px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;" ui-temp ui-style ui-temp-index="3"></section></section><section style="width: 14em; height: 1.2em; margin: 1em auto 0;"></section></section></fieldset>'),
		a.put("temp/img/img19.html",'<fieldset style="border: none; margin:0; text-align:center;"><section><img style="box-sizing: border-box; width: 100%;height: auto !important;" ui-temp="img" ui-temp-index="0"><section style="margin-top: 0.1em; width: 100%;"><img style="box-sizing: border-box; width: 49%;height: auto !important;float: left;" ui-temp="img" ui-temp-index="1"><img style="box-sizing: border-box;float: right;width: 49%; height: auto !important;text-align: start;" ui-temp="img" ui-temp-index="2"></section></section></fieldset>'),
		a.put("temp/img/img20.html",'<fieldset style="border: none; margin:0; text-align:center;"><section><img style="display: inline-block; width: 70%;box-sizing: border-box; text-align: start" ui-temp="img" ui-temp-index="0"><section style="display: inline-block; width: 28%; padding: 0.5em 0px 0px 0.5em; vertical-align: top;" ui-temp ui-style ui-temp-index="0"></section></section><section><img style="float: right; width: 55%;margin-top: -3em; box-sizing: border-box; text-align: start" ui-temp="img" ui-temp-index="1"><section style="display: inline-block; width: 45%;  padding: 0 0.1em 0 0; box-sizing: border-box; float: right; text-align: right"><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px; color: rgb(52, 54, 60); font-size: 1.2em; border-bottom-width: 1px; border-bottom-style: solid; border-color: black;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp ui-style ui-temp-index="1"></section><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px;" ui-temp ui-style ui-temp-index="2"></section></section></section></fieldset>'),
		a.put("temp/img/img21.html",'<fieldset style="border: none; margin:0; text-align:center;"><section><img style="display: inline-block; width: 70%;box-sizing: border-box; text-align: start" ui-temp="img" ui-temp-index="0"><section style="display: inline-block; width: 28%; padding: 0.5em 0px 0px 0.5em; vertical-align: top;" ui-temp ui-style ui-temp-index="0"></section></section><section><img style="float: right; width: 55%;margin-top: -3em; box-sizing: border-box; text-align: start" ui-temp="img" ui-temp-index="1"><section style="display: inline-block; width: 45%;  padding: 0 0.1em 0 0; box-sizing: border-box; float: right; text-align: right"><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px; color: rgb(52, 54, 60); font-size: 1.2em; border-bottom-width: 1px; border-bottom-style: solid; border-color: black;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}" ui-temp ui-style ui-temp-index="1"></section><section style="box-sizing: border-box; margin-right: 4px; padding: 4px 6px;" ui-temp ui-style ui-temp-index="2"></section></section></section></fieldset>'),
		
		/*----------------------顶关注-------------------------*/
		a.put("temp/top/top1.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="width: 100%; line-height:0;" ui-temp="img"></fieldset>'),
		a.put("temp/top/top2.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="width: 30px; margin: 0 auto; display: inline-block;vertical-align: middle" ui-temp="img"><section style="display:inline-block; vertical-align: middle; font-size: 12px; margin-left:5px;" ui-temp></section></section></fieldset>'),
		
		/*----------------------底阅读-------------------------*/
		a.put("temp/bottom/bottom1.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="width: 100%; line-height:0;" ui-temp="img"></fieldset>'),
		a.put("temp/bottom/bottom2.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="width: 30px; margin: 0 auto; display: inline-block;vertical-align: middle" ui-temp="img"><section style="display:inline-block; vertical-align: middle; font-size: 12px; margin-left:5px;" ui-temp></section></section></fieldset>'),
		a.put("temp/bottom/bottom3.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><section style="box-sizing: border-box;"><section style="box-sizing: border-box;" ui-temp ui-style></section></section><section style="width: 100%; border-bottom-width: 2px; border-bottom-style: solid; border-color: rgb(131, 16, 114) rgb(142, 201, 101) rgb(56, 203, 245); box-sizing: border-box;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="width: 0px; margin: 0px 8%; border-top-width: 1em; border-top-style: solid; border-top-color: rgb(56, 203, 245); border-bottom-color: rgb(7, 87, 52); box-sizing: border-box; border-left-width: 0.8em !important; border-left-style: solid !important; border-left-color: transparent !important; border-right-width: 0.8em !important; border-right-style: solid !important; border-right-color: transparent !important;" ng-style="{\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></fieldset>'),
		a.put("temp/bottom/bottom4.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><section style="display: inline-block; padding: 5px; margin-left: 3em; box-sizing: border-box;"><section style="box-sizing: border-box;" ui-temp ui-style></section></section><section style="text-align: right; width: 100%; box-sizing: border-box;"><section style="width: 99.9%; float: left; height: 0px; border-top-width: 1px; border-top-style: solid; box-sizing: border-box;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="width: 6px; height: 6px; margin-top: -3px; background-color: rgb(0, 187, 236); float: right; border-top-left-radius: 100%; border-top-right-radius: 100%; border-bottom-right-radius: 100%; border-bottom-left-radius: 100%; box-sizing:" ng-style="{\'background-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section><section style="width: 36px; height: 36px; margin-top: -18px; display: inline-block; border: 1px solid rgb(0, 187, 236); border-top-left-radius: 100%; border-top-right-radius: 100%; border-bottom-right-radius: 100%; border-bottom-left-radius: 100%; box-sizing: border-box;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="width: 100%; height: 100%; border-top-left-radius: 100%; border-top-right-radius: 100%; border-bottom-right-radius: 100%; border-bottom-left-radius: 100%;" ui-temp="img" data-backimg></section></section></fieldset>'),

		/*----------------------标题卡片-------------------------*/
		a.put("temp/visitingcard/visit1.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin:0 16px; padding: 0; border: none;"><section style="display: inline-block; padding: 3px 8px; border-radius: 4px;" ui-temp ui-style ui-temp-index="0"></section></section><section style="padding: 16px;margin-top:-0.8em;border:solid 1px #ddd;" ui-temp ui-style ui-temp-index="1"></section></fieldset>'),
		a.put("temp/visitingcard/visit2.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin:0 16px; padding: 0; border: none;"><section style="display: inline-block; padding: 3px 8px; border-radius: 4px; margin-right:0.5em;" ui-temp ui-style ui-temp-index="0"></section><section style="display: inline-block; padding: 3px 8px; border-radius: 4px;" ui-temp ui-style ui-temp-index="1"></section></section><section style="padding: 16px;margin-top:-0.8em;border:solid 1px #ddd;" ui-temp ui-style ui-temp-index="2"></section></fieldset>'),
		a.put("temp/visitingcard/visit3.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin:0 16px; padding: 0; border: none; text-align:center;"><section style="display: inline-block; padding: 3px 8px; border-radius: 4px;" ui-temp ui-style ui-temp-index="0"></section></section><section style="margin-top:-0.8em;padding: 16px;border:solid 1px #ddd;" ui-temp ui-style ui-temp-index="1"></section></fieldset>'),
		a.put("temp/visitingcard/visit4.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin:10px 16px 16px; padding: 8px 16px; text-align: center; border: 1px solid rgb(204, 204, 204); display: block;"><section style="margin-top: -1.2em; padding: 0; border: none"><section style="display: inline-block; padding:4px 14px; border-radius:0px 2em 0px 2em" ui-temp ui-style ui-temp-index="0"></section></section><section style="padding: 16px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/visitingcard/visit5.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin-left: -0.5em;"><section style="display: inline-block; padding: 3px 8px; border-radius: 4px;transform: rotateZ(-10deg); transform-origin: left bottom 0px;" ui-temp ui-style ui-temp-index="0"></section></section><section style="margin-top: -24px; padding: 22px 16px 16px; border: 1px solid rgb(249, 110, 87);" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: inline-block; float: left;" ui-temp ui-style ui-temp-index="1"></section><section ui-temp ui-style ui-temp-index="2"></section></section></fieldset>'),
		a.put("temp/visitingcard/visit6.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section><section style="padding: 3px 8px; border-top-left-radius: 8px; border-top-right-radius: 8px;" ui-temp ui-style ui-temp-index="0"></section><section style="box-sizing: border-box; border-style: solid; border-width: 0px 1px 1px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 16px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" ui-temp ui-style ui-temp-index="1"></section></section></section></fieldset>'),
		a.put("temp/visitingcard/visit7.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: inline-block; padding: 3px 8px; border-top-left-radius: 8px; border-top-right-radius: 8px;" ui-temp ui-style ui-temp-index="0"></section><section style="margin-top: -1px; box-sizing: border-box; border: 1px solid rgb(249, 110, 87); border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 16px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" ui-temp ui-style ui-temp-index="1"></section></section></section></fieldset>'),
		a.put("temp/visitingcard/visit8.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="margin: 1em; padding: 0px; white-space: normal; text-align: left; border: 1px solid rgb(204, 204, 204); display: block; background-color:#f7f7f7;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="margin: -0.8em 16px 0px; padding: 0px;  text-shadow: rgb(204, 204, 204) 4px 3px;" ui-temp ui-style ui-temp-index="0"></section><section style="padding: 13px 16px 16px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/visitingcard/visit9.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:10.5em 0;"><section style="width: 2.5em; height: 2.5em; margin: 0px auto 1px; text-align: center; border-radius: 100%; line-height: 2.5em; box-sizing: border-box; overflow: hidden;" ui-temp ui-style ui-temp-index="0"></section><section><section style="width: 0px; margin: 0px auto; border-bottom-width: 5px; border-bottom-style: solid; border-bottom-color: rgb(249, 110, 87); border-left-width: 10px !important; border-left-style: solid !important; border-left-color: transparent !important; border-right-width: 10px !important; border-right-style: solid !important; border-right-color: transparent !important;" ng-style="{\'border-bottom-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="width: 100%; padding: 0.5em;box-sizing:border-box;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/visitingcard/visit10.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="width: 90%; margin-left: 5%; height: 100%; border: 1px solid rgb(204, 204, 204); border-radius: 0px 5px 5px 0px;"><section style="margin-top: 5%; float: left; margin-right: 8px; margin-left: -8px;"><section style="display: inline-block; padding: 0.3em 0.5em; border-radius: 0px 0.5em 0.5em 0px" ui-temp ui-style ui-temp-index="0"></section><section style="width: 0px; border-right-width: 4px; border-right-style: solid; border-right-color: rgb(249, 110, 87); border-top-width: 4px; border-top-style: solid; border-top-color: rgb(249, 110, 87); border-left-width: 4px !important; border-left-style: solid !important; border-left-color: transparent !important; border-bottom-width: 4px !important; border-bottom-style: solid !important; border-bottom-color: transparent !important;" ng-style="{\'border-right-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section><section style="display: block; margin-top: 5%; padding: 0px 8px;line-height:1.75em;" ui-temp ui-style ui-temp-index="1"></section><section style="clear: both"></section><section style="padding: 8px" ui-temp ui-style ui-temp-index="2"></section></section></fieldset>'),
		a.put("temp/visitingcard/visit11.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="margin-bottom: -4.2em; display: inline-block; vertical-align: bottom;width:100%;"><section style="width:100%;" ui-temp ui-style ui-temp-index="0"></section><section><section style="width: 0px; float: left; border-right-width: 4px; border-right-style: solid; border-right-color: rgb(249, 110, 87); border-top-width: 4px; border-top-style: solid; border-top-color: rgb(249, 110, 87); border-left-width: 4px !important; border-left-style: solid !important; border-left-color: transparent !important; border-bottom-width: 4px !important; border-bottom-style: solid !important; border-bottom-color: transparent !important;" ng-style="{\'border-right-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section><section style="width: 0px; float: right; border-left-width: 4px; border-left-style: solid; border-left-color: rgb(249, 110, 87); border-top-width: 4px; border-top-style: solid; border-top-color: rgb(249, 110, 87); border-right-width: 4px !important; border-right-style: solid !important; border-right-color: transparent !important; border-bottom-width: 4px !important; border-bottom-style: solid !important; border-bottom-color: transparent !important;" ng-style="{\'border-left-color\':value.text[0].edit.defaultColor || value.defaultColor,\'border-top-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></section><section style="width: 100%; padding:0 8px; box-sizing:border-box;"><section style="border: 1px solid rgb(204, 204, 204); padding: 5em 5px 1em; border-radius: 0.3em; box-shadow: rgba(159, 160, 160, 0.498039) 0px 0px 10px;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),

		/*----------------------其他-------------------------*/
		a.put("temp/other/other1.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="border: 1px solid rgb(201, 201, 201); text-align:center;"><section style="width: 10em; height: 10em; margin: 16px auto; padding: 0.5em; border: 1px solid rgb(249, 110, 87); border-radius: 100%;" ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="display: table; width: 100%; height: 100%; border-radius: 50%; text-align:center"><section style="display: table-cell; vertical-align: middle;border-radius: 50%;" ui-temp ui-style ui-temp-index="0"></section></section></section><section style="display: inline-block; height: 2em; max-width: 100%; padding:0 1em; margin: 16px auto 32px auto; border-radius: 1em; line-height:2em;" ui-temp ui-style ui-temp-index="1"></section></section><section style="padding: 16px; border-left-width: 1px; border-left-style: solid; border-color: rgb(201, 201, 201); border-right-width: 1px; border-right-style: solid; border-bottom-width: 1px; border-bottom-style: solid;" ui-temp ui-style ui-temp-index="2"></section></fieldset>'),
		a.put("temp/other/other2.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="display: inline-block;width: 60px; vertical-align: top"><section style="width: 40px; height: 40px; margin-left: 10px; border-radius: 40px;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style=" text-align:center;" ui-temp ui-style ui-temp-index="0"></section></section><section style="display: inline-block; width: 75%;"><img style="vertical-align: top; margin-top: 18px; background-color: rgb(255, 228, 200);" ng-style="{\'background-color\': value.text[1].edit.backgroundColor || value.defaultColor}" ui-temp="img" ui-temp-index="1"><section style="display: inline-block; width: 85%; padding: 16px; border-radius: 16px; background-color: rgb(255, 228, 200);box-sizing: border-box;" ui-temp ui-style ui-temp-index="1"></section></section></fieldset>'),
		a.put("temp/other/other3.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="display: inline-block; vertical-align: top; width: 3em; height: 3em;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="display: inline-block; vertical-align: top; margin-left: 5px; width: 80%;"><section style="margin-left: 1.2em; padding-right: 10px; line-height:1.25em;" ui-temp ui-style ui-temp-index="0"></section><section style="width: 0px; margin-left: 0.1em; border-right-width: 8px; border-right-style: solid; border-right-color: rgb(249, 110, 87); display: inline-block; margin-top: 12px; vertical-align: top; border-top-width: 6px !important; border-top-style: solid !important; border-top-color: transparent !important; border-bottom-width: 6px !important; border-bottom-style: solid !important; border-bottom-color: transparent !important;" ng-style="{\'border-right-color\':  value.text[1].edit.defaultColor || value.defaultColor}"></section><section style="width: 95%;display: inline-block;vertical-align: middle"><section style="padding: 10px; display: inline-block; border-radius: 0.5em;" ui-temp ui-style ui-temp-index="1"></section></section></section></fieldset>'),
		a.put("temp/other/other4.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="display: inline-block; width: 75%;text-align: right;"><section style="display: inline-block; width: 85%; margin-top: 11px; padding: 16px; border-radius: 16px; text-align: left;box-sizing:border-box;" ng-style="{\'background-color\':value.text[1].edit.backgroundColor || value.defaultColor}" ui-temp ui-style ui-temp-index="1">请输入对话</section><img style="vertical-align: top; margin-top: 29px; background-color: rgb(255, 228, 200);" ng-style="{\'background-color\': value.text[1].edit.backgroundColor || value.defaultColor}" ui-temp="img" ui-temp-index="1"></section><section style="display: inline-block; vertical-align: top;width: 60px"><section style="width: 40px; height: 40px; margin-left: 10px; border-radius: 40px;" ui-temp="img" data-backimg ui-temp-index="0"></section><section style="text-align:center;" ui-temp ui-style ui-temp-index="0">请输入</section></section></fieldset>'),
		a.put("temp/other/other5.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="display: inline-block; vertical-align: top; margin-left: 5px; width: 80%; text-align:right;"><section style="padding: 0px 20px 0px 10px;text-align:right;" ui-temp ui-style ui-temp-index="0"></section><section style="width: 95%;display: inline-block;vertical-align: middle; box-sizing:border-box;"><section style="padding: 10px; display: inline-block; border-radius: 0.5em;" ui-temp ui-style ui-temp-index="1"></section></section><section style="width: 0px; border-left-width: 8px; border-left-style: solid; border-left-color: rgb(249, 110, 87); display: inline-block; margin-top:12px; vertical-align: top; border-top-width: 6px !important; border-top-style: solid !important; border-top-color: transparent !important; border-bottom-width: 6px !important; border-bottom-style: solid !important; border-bottom-color: transparent !important;" ng-style="{\'border-left-color\':  value.text[1].edit.defaultColor || value.defaultColor}"></section></section><section style="display: inline-block; vertical-align: top; margin-left: 5px; width: 3em; height: 3em;" ui-temp="img" data-backimg ui-temp-index="0"></section></fieldset>'),
		a.put("temp/other/other6.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both;margin:0.5em 0;"><section style="border: 1px solid rgb(226, 226, 226); box-shadow:0 0 10px rgba(0, 0, 0,0.3);"><section style="padding: 20px;" ui-temp ui-style ui-temp-index="0"></section><section style="margin-top: 24px;"><img style="width: 30px; vertical-align: top; margin-left: 16px;" ng-style="{\'background-color\': value.text[0].edit.defaultColor || value.defaultColor}" ui-temp="img" ui-temp-index="0"><section style="display: inline-block; width: 60%; padding: 3px; margin-left: 8px;" ui-temp ui-style ui-temp-index="1"></section></section><section style="margin-top: 16px;"><img style="width: 30px; vertical-align: top; margin-left: 16px;" ng-style="{\'background-color\': value.text[0].edit.defaultColor || value.defaultColor}" ui-temp="img" ui-temp-index="1"><section style="display: inline-block; width: 60%; padding: 3px; margin-left: 8px;" ui-temp ui-style ui-temp-index="2"></section></section><section style="display: inline-block; height: 2em; max-width: 100%; margin-top: 24px; white-space: nowrap; text-overflow: ellipsis; line-height:2em;padding:0 1em;" ui-temp ui-style ui-temp-index="3"></section><section style="padding: 16px;" ui-temp ui-style ui-temp-index="4"></section></section></fieldset>'),
		a.put("temp/other/other7.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="width: 100%; line-height:0;" ui-temp="img"></fieldset>'),
		a.put("temp/other/other8.html",'<fieldset style="border:0;box-sizing: border-box; width: 100%; clear: both"><img style="max-width:100%; margin: 0 auto; display: inline-block;vertical-align: middle" ui-temp="img"><section style="display:inline-block; vertical-align: middle; font-size: 12px; margin-left:5px;" ui-temp></section></section></fieldset>'),
		a.put("temp/other/other9.html",'<fieldset style="border: none; margin:0; "><img style="width:8em;height:8em;float:left;padding: 1.2em;margin-top: 0.4rem;" ui-temp="img" ui-temp-index="1"><section style="box-sizing: border-box; width: 100%;height:14em;" ui-temp="img" ui-temp-index="0" data-backimg></section></fieldset>'),
		a.put("temp/other/other10.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 1px solid black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other11.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 3px solid black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other12.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 5px solid black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other13.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 5px dashed black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other14.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 5px dotted black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other15.html",'<fieldset style="border:0;margin:0.5em 0;text-align: center; font-size: 1em;vertical-align: middle; white-space: nowrap"><section style="padding: 0.5em; " ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"><section style="padding: 0px; margin: 0px; border-top: 5px double black;" ui-temp ui-style ng-style="{\'border-color\':value.text[0].edit.defaultColor || value.defaultColor}"></section></section></fieldset>'),
		a.put("temp/other/other16.html",'<fieldset style="border: none; margin:0; text-align:center;width:21.7em; height:auto; margin:0 auto; font-size:12px;border: 2px solid rgb(190, 163, 123); "><img style="box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"><section style="padding:1em; line-height: 2em; position: relative; z-index:1;display: inline-block;  box-sizing: border-box; text-align:center;" ><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section></section></fieldset>'),
		a.put("temp/other/other17.html",'<fieldset style="border-radius: 20px 40px; margin:0; text-align:center;width:21.7em; height:auto; margin:0 auto; font-size:12px;border: 2px solid rgb(190, 163, 123); "><section style="padding:1em; line-height: 2em; position: relative; z-index:1;display: inline-block;  box-sizing: border-box; text-align:center;" ><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section></section><img style="border-radius: 20px 40px;box-sizing: border-box;width: 100%;height: auto !important;text-align: start" ui-temp="img"></fieldset>'),
		a.put("temp/other/other18.html",'<fieldset style="border: none; margin:0; text-align:center;width:21.7em; height:auto; margin:0 auto; font-size:12px;"><img style="box-sizing: border-box;width: 160px;height: 70px;text-align:start;z-index:2;" ui-temp="img"><section style="border:2px solid rgb(21, 133, 47); margin-top: -1.5em;padding:1em; line-height: 2em; position: relative; display: inline-block;  box-sizing: border-box; text-align:center;z-index:1;" ><section style="display: inline-block; padding: 0.2em 0.2em 0.2em 0px;" ui-temp ui-style ui-temp-index="0"></section></section></fieldset>')

}]);