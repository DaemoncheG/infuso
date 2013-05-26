/**
 * Объект для работы с хэш-тэгом
 **/

inx.direct = {

    /**
     * Приватный метод. Вызывается по таймеру и проверяет изменения хэша
     **/
    check:function() {
        var h = (window.location.hash+"").substr(1).split("/");
        var hash = [];
        for(var i in h) if(h[i]) hash.push(h[i])
        hash = hash.join("/");
        if(inx.direct.last!=hash) {
            inx.direct.handleChange(hash);
            inx.direct.last = hash;
        }
    },
    
    /**
     * Метод, реагирующий на изменения
     **/
    handleChange:function(h) {
        var segments = h.split("/");
        
        var params = {};
        var action = segments[0];
        for(var i=1;i<segments.length;i++) {
            if(i%2==0) {
                params[key] = segments[i];
            } else {
                key = segments[i];
            }
        }
        
        if(inx.direct.id) {
            inx(inx.direct.id).cmd(inx.direct.fn,{
                action:action,
                params:params,
                segments:segments
            });
        }
    },
    
    /**
     * Возвращает текущий хэш
     **/
    get:function(n) {
        var h = (window.location.hash+"").substr(1);
        var a = h.split("/");
        return a[n];
    },
   
    /**
     * Устанавливает текущий хэш
     **/
    set:function() {
        var a = [];
        for(var i=0;i<arguments.length;i++) {
            a.push(arguments[i])
        }
        a = a.join("/");
        window.location.hash = a;
        this.check();
    },
    
    /**
     * Устанавливает кэллбэк на изменение хэша
     **/
    bind:function(id,fn) {
        inx.direct.id = inx(id).id();
        inx.direct.fn = fn;
    }
}
setInterval(inx.direct.check,100);