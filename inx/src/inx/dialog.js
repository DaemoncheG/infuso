// @include inx.panel

inx.dialog = inx.panel.extend({

    /**
     * clipTo - прикрепить диалог к произвольному элементу
     * clipToOwner - прикрепить диалог к родителю
     **/

    constructor:function(p) {
    
        this.x = p.x || 0;
        this.y = p.y || 0;
        
        this.centred = !this.x && !this.y;
        
        p.border =0;
        if(p.shadeOpacity===undefined) p.shadeOpacity = .3;
        
        if(p.padding===undefined)
            p.padding = 0;
        if(p.showTitle===undefined)
            p.showTitle = true;
        if(p.modal===undefined)
            p.modal = true;       
        if(p.closeButton===undefined)
            p.closeButton = true;  
            
        p.height = "content";
               
        this.base(p);
        this.on("titleChanged",[this.id(),"private_handleTitleChanged"]);
    },
    
    cmd_private_handleTitleChanged:function(title) {
        inx(this.titleBar).cmd("setTitle",title);
    },
    
    cmd_destroy:function() {
        this.base();
        $(this.mate).remove();
        $(this.private_wnd).remove();
        inx(this.titleBar).cmd("destroy");
        inx.fire("autofocus");
    },
    
    cmd_render:function() {       
    
        // Создаем затенение
        if(this.modal) {
            this.mate = $("<div style='width:100%;height:100%;background:#000000;position:fixed;left:0px;top:0px;' ></div>").css("opacity",0).appendTo("body");
            this.mate.animate({opacity:this.shadeOpacity})
            this.mate.css("zIndex",inx.conf.z_index_dialog);
        }
        
        this.private_wnd = $("<div>").addClass("inx-shadowframe").css({
            background:this.background || "#ededed",
            position:"absolute",
            zIndex:inx.conf.z_index_dialog
        }).appendTo("body");
        
        var c = $("<div>").css("margin","0px 0px 3px 0px").appendTo(this.private_wnd);
        if(this.showTitle) {
            this.titleBar = inx.cmp.create({
                type:"inx.dialog.title",
                height:20,
                width:this.info("width"),
                closeButton:this.closeButton,
                title:this.title
            }).cmd("render").cmd("appendTo",c);
            this.titleBar.on("drag",[this.id(),"drag"]);
            this.titleBar.setOwner(this);
        }
        
        this.base();
        
        var that = this;
        setTimeout(function(){
            that.cmd("appendTo",that.private_wnd);
        })
        
        if(this.centred) this.cmd("center");
        
        if(!this.info("hidden"))
            this.cmd("startPositionWatch");
            
        this.private_wnd.css({opacity:0}).animate({opacity:1});
    },
    
    cmd_hide:function() {
        $(this.private_wnd).hide();
        $(this.mate).hide();
        this.base();
        this.cmd("stopPositionWatch");
    },
    
    cmd_show:function() {
        $(this.private_wnd).show();
        $(this.mate).show();
        this.base();
        this.cmd("startPositionWatch");
    },
    
    cmd_setPosition:function(x,y,keep) {
        this.x = x;
        this.y = y;
        this.private_wnd.css("left",x).css("top",y);
    },
    
    cmd_startPositionWatch:function() {
        if(!this.private_iid)
            this.private_iid = setInterval(inx.cmd(this,"updatePosition"),100);
        this.task("updatePosition");
    },
    
    cmd_stopPositionWatch:function() {
        clearInterval(this.private_iid);
        this.private_iid = false;
    },
    
    cmd_updatePosition:function() {
    
        // Прикрепление к родителю
        if(this.clipToOwner) 
            this.clipTo = this.owner().info("param","el");
            
        // Прикрепление к элементу            
        if(this.clipTo) {
            this.cmd("updateClip");
        } else {
            if(this.centred)
                this.cmd("center");
        }

        // Не даем окну выходить за пределы экрана
        var o = 10;
        var mx = $(window).width() - $(this.private_wnd).outerWidth()-o;
        var my = $(window).height() - $(this.private_wnd).outerHeight()-o;
        if(this.x>mx) this.x = mx;
        if(this.y>my) this.y = my;
        if(this.x<o) this.x=o;
        if(this.y<o) this.y=o;
        
        this.cmd("setPosition",this.x,this.y);
    },    

    // При синхронизации окна, обновляем его позицию    
    cmd_syncLayout:function() {
        //this.task("updatePosition");
        this.base();
    },
    
    // Выставляем окно по прищепке
    cmd_updateClip:function() {
        var p = $(this.clipTo).offset();
        p.top += + $(this.clipTo).outerHeight();
        p.top -= $(window).scrollTop();
        this.cmd("setPosition",p.left,p.top);
    },
    
    // Центрирует окно
    cmd_center:function() {
        var x = $(window).width()/2 - this.info("width")/2;
        var y = $(window).height()/2 - this.info("height")/2;
        this.cmd("setPosition",x,y);
    },
    
    cmd_handleSmoothBlur:function() {
        if(this.autoDestroy) this.task("destroy");
        if(this.autoHide) this.task("hide");
        if(this.autoHide) this.task("hide");
        this.base();
    },
    
    cmd_keydown:function(e) {
        if(e.keyCode==27) {
            if(this.autoDestroy)
                this.task("destroy");
            if(this.autoHide)
                this.task("hide");
        }
        this.base(e);
    },
    
    // Обработчик перетаскивания
    cmd_drag:function(p) {

        if(p.phase=="start") this.cmd("stopPositionWatch");
        if(p.phase=="stop") this.cmd("startPositionWatch");
        this.centred = false;

        this.x+=p.dx;
        this.y+=p.dy;
        this.cmd("setPosition",this.x,this.y);
    }
    
});