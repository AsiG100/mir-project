
$(document).ready(function(){
    addSwitch();
});

$('.add-radio').on('click', function(){
    addSwitch();
});

//clears the revent display
$('#clear').on('click', function(){
    $('#res').html('');
});

//switch between add package and add item
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