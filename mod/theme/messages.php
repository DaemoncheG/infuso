<?

<div class='gveialugfd'> 

    tmp_lib::components();
    
    foreach(mod_log::messages() as $msg) {
    
        $class = $msg->error() ? "error" : "ok";
        
        <div class='$class' >
            echo $msg->text();
            
            $n = $msg->count();
            if($n > 1) {
                <span> ({$n})</span>
            }
            
        </div>
        
    }

</div>