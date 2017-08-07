var wavesurfer = [], loaded = [], ws_i = 0;
jQuery(function($){
    $('.djpress_wave').each(function(){
    ws_i++;

    wavesurfer[ws_i] = Object.create(WaveSurfer);
    wavesurfer[ws_i].init({
        container: $(this)[0],
        //pixelRatio:1,
        minPxPerSec:0.4,
        scrollParent:true,
        waveColor: $(this).attr('wavecolor') ? $(this).attr('wavecolor') : 'violet',
        progressColor: $(this).attr('progresscolor') ? $(this).attr('progresscolor') : 'purple',
        dragSelection: false
    });
    //wavesurfer.on('ready', function () { wavesurfer.play(); });
    var $bar = $("<tr></tr>");
    loaded[ws_i] = false;
    $bar.append($("<th>Play / Pause</th>").addClass("playpause").css({'cursor':'pointer', 'font-weight':'bold'}).attr('ws',ws_i).attr('url', "/?djpress_listen="+$(this).attr('id')).click(function(){
            if(!loaded[ws_i]){
                wavesurfer[ws_i].loadStream($(this).attr('url'));
            }
            wavesurfer[ws_i].playPause();
        }))
        .append($("<th></th>").html("Plays:"))
        .append($("<td></td>").html($(this).attr('pls')))
        .append($("<th></th>").html("Downloads:"))
        .append($("<td></td>").html($(this).attr('dls')))
        .append($("<td></td>").addClass('download').html("<a href='/?djpress_download="+$(this).attr('id')+"'>Download</a>"));
    $(this).after($('<table></table>').addClass('djpress_bar').append($bar));
});
    setTimeout(function(){ var ws = jQuery('.djpress_bar th').first().attr('ws'); var url = jQuery('.djpress_bar th').first().attr('url'); wavesurfer[ws].loadStream(url); loaded[ws] = true;}, 1000);

});