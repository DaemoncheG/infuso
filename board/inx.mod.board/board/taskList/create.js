// @link_with_parent


inx.mod.board.board.taskList.create = inx.panel.extend({

    constructor:function(p) {    
        p.style = {
            padding:15
        };
        
        p.side = [{
            region:"left",
            width:240,
            layout:"inx.layout.column",
            style:{
                padding:15
            },items:[{
                html:"<b>Новая задача</b>",
                width:100
            },{
                type:"inx.textfield",
                name:"search",
                onchange:[this.id(),"requestData"],
                width:110
            }]
        }];
        
        this.base(p);
        this.cmd("requestData");
    },
    
    cmd_requestData:function() {
    
        this.call({
            cmd:"board/controller/project/listProjectsSimple",
            search:inx(this).axis("side").allItems().eq("name","search").info("value")
        },[this.id(),"handleData"]);
    
    },
    
    cmd_handleData:function(data) {
    
        var e = $("<div>").css({
            whiteSpace:"nowrap"
        });
        
        for(var i in data) {
            $("<div>")
                .css({
                    display:"inline-block",
                    fontSize:16,
                    marginRight:10
                }).html(data[i].text)
                .click(inx.cmd(this,"newTask",data[i].id))
                .appendTo(e);
        }
    
        this.base();
        this.cmd("html",e);
       
    },
    
    cmd_newTask:function(projectID) {
        this.call({
            cmd:"board/controller/task/newTask",
            projectID:projectID
        },[this.id(),"handleCreateNewTask"]);
    },
    
    cmd_handleCreateNewTask:function(data) {
        if(!data) {
            return;
        }
        window.location.hash = "task/id/" + data;
    }
         
});