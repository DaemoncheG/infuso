var form = function(selector,hash) {

    $(selector).submit(function(e) {

        var form = $(this);
        e.preventDefault();
        var data = $(this).serializeArray();
        var ret = {};
        for(var i in data) {
            ret[data[i].name] = data[i].value;
        }
        var data = ret;
        delete data.cmd;

        mod.cmd({
            cmd:"form_validate:validate",
            data:data,
            hash:hash
        },function(d) {

            form.find(".lbdmv238az").hide("fast");

            // Если форма валидна, отправляем ее
            if(d.valid) {
                form.unbind("submit");
                form.submit();

            // Если форма не валидна, показываем сообщение об ошибке
            } else {

                var field = form.find("[name="+d.name+"]");
                var msg = form.find(".error-"+d.name);

                if(!field.length) {
                    mod.msg("Element <b>[name="+d.name+"]</b> not found inside <b>"+selector+"</b>",1);
                }

                if(!msg.length) {
                    mod.msg("Element <b>.error-"+d.name+"</b> not found inside <b>"+selector+"</b>",1);
                }

                // Фокусируемся на элементе с ошибкой если он видимый
                if(field.filter(":visible").length) {
                    field.focus();
                } else {
                    $("<input>").appendTo(msg).focus().remove();
                }

                msg.html(d.html).hide().addClass("lbdmv238az").show("fast");
            }
        });
    });

}
