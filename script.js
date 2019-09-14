
$(document).ready(function(){
    addSwitch();
});

$('.add-radio').on('click', function(){
    addSwitch();
});

$('#clear').on('click', function(){
    $('#res').html('');
});

function addSwitch(){
    state = $('.add-radio:checked').val();
    
    if(state === 'package'){
        $('.serial').hide();
        $('.cat').show();
    }else{
        $('.cat').hide();
        $('.serial').show();
    }
}