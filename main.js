var main_url = "http://localhost/pipweb";

function disable_input() {
    document.getElementById('fpslabel').style.color = '#808080';
    document.getElementById('durationlabel').style.color = '#808080';
    document.getElementById('frameslabel').style.color = '#808080';
    document.getElementById('fps').disabled = true;
    document.getElementById('duration').disabled = 'disabled';
    document.getElementById('frames').disabled = true;
    document.getElementById('fps_tunit').disabled = true;
    document.getElementById('duration_tunit').disabled = true;
}

function enable_input() {
    document.getElementById('fpslabel').style.color = 'black';
    document.getElementById('durationlabel').style.color = 'black';
    document.getElementById('frameslabel').style.color = 'black';
    document.getElementById('fps').disabled = false;
    document.getElementById('duration').disabled = false;
    document.getElementById('frames').disabled = false;
    document.getElementById('fps_tunit').disabled = false;
    document.getElementById('duration_tunit').disabled = false;
}

function viewhistory() {
    document.getElementById('myframe').src = 'about:blank';
    document.getElementById('myframe').style.display = '';
    document.getElementById('myframe').src = 'filebrowser/index.php';
    document.getElementById('myframe').style.display = '';
    document.getElementById('myframe').scrolling = 'yes';
    is_recording();
    disable_input();
}

function viewdisplay() {
    document.getElementById('myframe').src = 'about:blank';
    document.getElementById('myframe').style.display = '';
    if( document.getElementById('proc').value == "edges" ) {
        document.getElementById('myframe').src = 'live_edges.html';
    } else {
        document.getElementById('myframe').src = 'display.html';
    }
    document.getElementById('myframe').style.display = '';
    document.getElementById('myframe').scrolling = 'no';
    is_recording();
    disable_input();
}

function record() {
    document.getElementById('myframe').src = 'about:blank';
    document.getElementById('myframe').style.display = '';
    document.getElementById('myframe').style.display = 'none';
    is_recording();
    enable_input();
}

function mysubmit() {
    var option = document.inputform.options;

    if( option[0].checked == true ) {
        var fps = document.getElementById('fps').value;
        var ftu = document.getElementById('fps_tunit').value;
        var duration = document.getElementById('duration').value;
        var dtu = document.getElementById('duration_tunit').value;
        var frames = document.getElementById('frames').value;
        var proc = document.getElementById('proc').value;
        
        var uspf = 0;
        if( ftu == "seconds" ) {
            uspf = 1/fps;
        } else if( ftu == "minutes" ) {
            uspf = 1/fps * 60;
        } else if( ftu == "hours" ) {
            uspf = 1/fps * 3600;
        } else {
            return;
        }

        if( dtu == "minutes" ) {
            duration = duration * 60;
        } else if( dtu == "hours" ) {
            duration = duration * 3600;
        } else if( dtu != "seconds" ) {
            return;
        }

        if( uspf < 0 || duration < 0 || frames < 0 ) {
            return;
        }

        if( proc != "edges" && proc != "copy" ) {
            return;
        }

        var url = main_url + "/record.php" +
                  "?resolution=320x240" +
                  "&frames=" + frames +
                  "&duration=" + duration +
                  "&uspf=" + uspf +
                  "&filter=" + proc;

        document.getElementById('myframe').style.display = '';
        document.getElementById('myframe').src = url;
    } else if( option[1].checked == true ) {
        viewdisplay();
    } else if( option[2].checked == true ) {
        viewhistory();
    }
}

function is_recording() {
    var xmlHttp = new XMLHttpRequest();
    if( xmlHttp === null ) {
        alert("Your browser does not support AJAX!");
        return;
    }

    var url = main_url + "/is_recording.php";
    xmlHttp.onreadystatechange = function() {
        if( xmlHttp.readyState == 4) {
            if( xmlHttp.status == 200 ) {
                if( xmlHttp.responseText == "1" ) {
                    document.getElementById('button').innerHTML = 'recording...';
                    document.getElementById('button').disabled = true;
                    document.getElementById('stopbutton').disabled = false;
                } else if( xmlHttp.responseText == "0" ) {
                    document.getElementById('button').innerHTML = 'execute!';
                    document.getElementById('button').disabled = false;
                    document.getElementById('stopbutton').disabled = true;
                }
            }
        }
    };
    xmlHttp.open( "GET", url, true );
    xmlHttp.send( null );
}

function stop_recording() {
    var xmlHttp = new XMLHttpRequest();
    if( xmlHttp === null ) {
        alert("Your browser does not support AJAX!");
        return;
    }

    var url = main_url + "/stop_recording.php";
    xmlHttp.onreadystatechange = function() {
        if( xmlHttp.readyState == 4) {
            if( xmlHttp.status == 200 ) {
                if( xmlHttp.responseText == "1" ) {
                    return true;
                }
                return false;
            }
        }
    };
    xmlHttp.open( "GET", url, true );
    xmlHttp.send( null );
    document.getElementById('stopbutton').disabled = true;
}
