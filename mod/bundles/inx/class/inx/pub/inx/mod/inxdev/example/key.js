// @include inx.panel
/*-- /inxdev/inx.mod.inxdev/example/key.js --*/


inx.ns("inx.mod.inxdev.example").key = inx.panel.extend({

    constructor:function(p) {
        p.items = [{type:"inx.textfield"}];
        this.base(p);
    },
    
    cmd_keydown:function(e) {
        inx.msg(e.which);
    }

});

