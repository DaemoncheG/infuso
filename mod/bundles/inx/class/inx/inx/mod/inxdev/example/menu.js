// @include inx.button
/*-- /inxdev/inx.mod.inxdev/example/menu.js --*/


inx.ns("inx.mod.inxdev.example").menu = inx.button.extend({

    constructor:function(p) {
        p.text = "Menu button";
        p.menu = [
            {text:777}
        ];
        this.base(p);
    } 
    

});

