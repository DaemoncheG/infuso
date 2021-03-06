// @link_with_parent

inx.css(".f50tpvh3plh-label{ padding-left:30px;cursor:pointer;background-repeat:no-repeat;}")

inx.layout["default"] = {

    create:function() {},
    
    add:function(id) {
    
        var cmp = inx(id);    
        if(cmp.data("ij89238v67"))
            return;
            
        // Контейнер панели
        var e = $("<div>").css({
            position:"absolute"
        }).appendTo(this.__body);
        cmp.data("layoutContainer",e);
        
        // Контейнер заголовка
        var te = $("<div>").addClass("f50tpvh3plh-label").css({
            position:"absolute"
        }).click(inx.cmd(cmp,"toggle"))
        .appendTo(this.__body);
        cmp.data("titleContainer",te);        
        
        cmp.cmd("appendTo",e);
        
        cmp.data("ij89238v67",true);
        
    },
    
    remove:function(cmp) {
        $(cmp.data("layoutContainer")).detach();
        $(cmp.data("titleContainer")).detach();
        cmp.data("ij89238v67",false);
    },
    
    sync:function() {     
    
        var padding = this.style("padding");
        var width = this.info("clientWidth");
        
        if(width<=0)
            return;
        
        var that = this;
        
        if(this.private_html===undefined) {
            
            var y = 0;
            var spacing = this.style("spacing");                
    
            this.items().each(function(n) {    
            
                var e = this.data("layoutContainer");
                if(!e)
                    return;
                    
                var doSpacing = false;
                
                var title = this.info("title");
                var t = this.data("titleContainer");   
                
                if(title) {
                                          
                    t.html(this.info("title"));
                    
                    t.css({
                        top:y,
                        left:0,
                        width:width-30, // Поправка на padding
                        display:"block",
                        backgroundImage:"url("+inx.img(this.info("hidden") ? "expand" : "collapse")+")"
                    })
                    
                    
                    var h = inx.height(t);
                    y += h;
                    
                    if(this.info("visible"))
                        y += that.style("titleMargin");                    
                    
                    doSpacing = true;
                    
                } else {
                    t.css("display","none");
                }
                
                if(this.info("visible")) {
                
                    e.css({
                        left:0,
                        top:y,
                        display:"block"
                    });
                    
                    this.cmd("width",width);
                    
                    y += this.info("height"); 
                    doSpacing = true;
                    
                } else {
                    e.css("display","none");
                }
                
                if(doSpacing)
                    y += spacing;
    
            })            
            
            y-= spacing;
            this.cmd("setContentHeight",y);
            
        } else {
    
            if(this.private_htmlContainer) {
                if(this.lastHTMLWidth!=width) {
                    this.private_htmlContainer.width(width);
                    this.lastHTMLWidth = width;
                }
            } 
                
            var contentHeight = inx.height(this.private_htmlContainer);
            this.cmd("setContentHeight",contentHeight);

        }        

    }
}
