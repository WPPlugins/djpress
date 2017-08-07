jQuery(function($){
    if( 0 < $('#post').length ) $('form').attr('enctype', 'multipart/form-data');
    if( 0 < $('.tracklist').length ) {
        function djpress_removetrack(e){ e.preventDefault(); $(this).parents('tr').first().remove(); }
        function djpress_addtrack(e){
            e.preventDefault();
            $('.tracklist').each(function(){
                var tracknumber = $(this).find('tr').last().find('td').first().html() * 1; tracknumber = tracknumber ? tracknumber : 0; tracknumber++;
                var $new = $("<tr><td>"+tracknumber+"</td><td><input name='djpress_tracklist["+tracknumber+"][song]' placeholder='Song Title'></td><td><input name='djpress_tracklist["+tracknumber+"][artist]' placeholder='Artist Name'></td><td><a href='#' class='removetrack'>Remove</a></td></tr>"); $new.find('a').click(djpress_removetrack);
                $(this).append($new);
            });
        }
        $('.removetrack').click(djpress_removetrack);
        $('.addtrack').click(djpress_addtrack);
        $('#djpress_mix_file .inside').append('<div><input accept="audio/*" id="post_media" type="file" name="djpress_file" value="" size="25" /></div>');
    }
});