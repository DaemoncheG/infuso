// @include inx.dd

inx.css(".fg2j4g6nplstfeb349so-left{vertical-align:middle;padding:3px;cursor:pointer;width:16px;height:16px;background:url("+inx.img("prev")+") center no-repeat}",".fg2j4g6nplstfeb349so-right{vertical-align:middle;padding:3px;cursor:pointer;width:16px;height:16px;background:url("+inx.img("next")+") center no-repeat}",".fg2j4g6nplstfeb349so-center{vertical-align:middle;height:16px;cursor:e-resize;}");inx.pager=inx.box.extend({constructor:function(p){p.border=0;p.activePage=1;p.height=22;this.range=10;this.base(p);if(p.onchange)this.on("change",this.onchange);if(p.value)this.cmd("setValue",p.value);},cmd_select:function(page){this.cmd_setValue(page);},cmd_setValue:function(page){page=page*1;if(!page||page<1)page=1;if(page>this.info("total"))page=this.info("total");if(this.activePage==page)return;this.activePage=page;this.update_pages();this.fire("change",page);},cmd_prev:function(){this.cmd("select",this.info("value")-1);},cmd_next:function(){this.cmd("select",this.info("value")+1);},info_value:function(){return this.activePage},info_total:function(){return this.totalPages},cmd_setTotal:function(t){t*=1;if(!t)t=1;if(t<1)t=1;this.totalPages=t;if(this.activePage>this.totalPages)
this.cmd("select",this.totalPages);this.update_pages();},update_pages:function(){this.cmd("displayPage",this.info("value"));},cmd_displayPage:function(page){if(!this.el)return;if(page<1)page=1;if(page>this.info("total"))page=this.info("total");var html="стр. "+page+"/"+this.info("total");this.center.html("<div>"+html+"</div>");},cmd_render:function(c){this.base(c);var cmpid=this.id();this.el.css("background","none");this.prev=$("<div>").addClass("inx-core-inlineBlock fg2j4g6nplstfeb349so-left").appendTo(this.el).click(function(){inx(cmpid).cmd("prev");});this.center=$("<div>").addClass("inx-core-inlineBlock fg2j4g6nplstfeb349so-center").appendTo(this.el);this.next=$("<div>").addClass("inx-core-inlineBlock fg2j4g6nplstfeb349so-right").appendTo(this.el).click(function(){inx(cmpid).cmd("next");});inx.dd.enable(this.el,this,"drag");this.cmd("setTotal",this.total);this.update_pages();},cmd_drag:function(p){if(p.phase=="start"){this.x=0;this.startDragPage=this.info("value");}
this.x+=p.dx;var page=Math.floor(this.startDragPage+this.x/10);if(p.phase=="stop")
this.cmd("select",page);else
this.cmd("displayPage",page);}});