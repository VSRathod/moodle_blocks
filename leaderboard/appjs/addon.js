(function(t) {
    t.block = '';
    t.showHideBlock = function(block) {
        var block = document.getElementById(block.CONTENT_OTHERDATA.block_id);
        var el = block.getElementsByClassName('card-text')[0];
        var bt = block.getElementsByClassName('expand-collapse-button')[0];
        if (el.style.display === "none") {
            el.style.display = "block";
            bt.innerHTML = "-";
        } else {
            el.style.display = "none";
            bt.innerHTML = "+";
        }
    }
    var intt = setInterval(function(){
        if (document.getElementById(t.CONTENT_OTHERDATA.block_id) != null && typeof document.getElementById(t.CONTENT_OTHERDATA.block_id) != "undefined"){
           if (document.getElementById(t.CONTENT_OTHERDATA.block_id).getElementsByClassName('block_content').innerHTML != ""){
                t.block = document.getElementById(t.CONTENT_OTHERDATA.block_id);
                clearInterval(intt);
                t.blockContentLoaded();
            }
        }
    }, 200);
    t.blockContentLoaded = function(){
        
    }
    
})(this);