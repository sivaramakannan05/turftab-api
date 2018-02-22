$(document).ready(function() {

  // Initiate variable with selectors
  var boxs = $('.boxs');
  var all = $('#allgame');
  var load = $('#loading');
  var game_status = $('#game_status_val').val();
  if(game_status == 2) {
    var win = false;
  }
  var ver = true;
  all.hide();
  
  setTimeout(function(){
    load.fadeOut('fast');
    all.fadeIn('slow');
  },1000);


  var game_status = function() {

    $.ajax({

      url : base_url+"game_update",
      type : "POST",
      data : {game_id:game_id},
      dataType : "json",
      success : function(res) {

        if(res) {

          if(res.tictactoe_status == 2) {

            if(win == false) {
              $('#game_status').html('Game started');  
            }
            var val = (res.playing_user == "p1") ? 'X' : 'O';
            $('#score').html('Playing Now : <b> '+val+' </b>');
            $('#game_status_val').val('2');
          }
          delete res.game_id;
          delete res.playing_user;
          delete res.tictactoe_status;
          delete res.tictactoe_updated_date;
          
          for (i in res) {
            if(res[i] == 'p1'){
              $('#'+i).html('<b class="b1">X<b/>')
              $('#'+i).css("background","#CCCCCC");
              $('#'+i).addClass('selected');
            }
            else if(res[i] == 'p2') {
              $('#'+i).html('<b class="b2">O<b/>')
              $('#'+i).css("background","#CCCCCC");
              $('#'+i).addClass('selected');
            }
            else {
              $('#'+i).html('')
              $('#'+i).css("background","#E8E8E8");
              $('#'+i).removeClass('selected');
            }
          }
        }
      }
    });
  };

game_status();
setInterval(game_status, 2000);

boxs.click(function() {

  if($(this).hasClass('selected')) {
    return false;
  }

  var hidden_val = $('#player_val').val();
  var total_selected_count = $('.selected').length;
  var game_status = $('#game_status_val').val();
  if(game_status != 2) {
    $.alert("You can't play now.");
    return false;
  }
  if(hidden_val == "p1" &&  (total_selected_count % 2) !== 0) {
    $.alert("Please wait for your turn.");
    return false;
  }
  else if(hidden_val == "p2" &&  (total_selected_count % 2) === 0) {
    $.alert("Please wait for your turn.");
    return false;
  }
  var sec = $(this).attr('id');
  var divt = $(this);

  $.ajax({

      url : base_url+"update_game_status",
      type : "POST",
      data : {game_id:game_id,sec:sec,player:hidden_val},
      dataType : "json",
      success : function(res) {

        if(res.status == "true") {

          if(hidden_val == 'p1'){
            $(divt).html('<b class="b1">X<b/>')
            $(divt).css("background","#CCCCCC");
            $(divt).addClass('selected');
          }
          else if(hidden_val == 'p2'){
            $(divt).html('<b class="b2">O<b/>')
            $(divt).css("background","#CCCCCC");
            $(divt).addClass('selected');
          }
        }
        else {
          $.alert(res.message);
        }
      }
    });
});
 
  setInterval(function() {

    if(win == false) {

      var value0 = $('#sec0').html();
      var value1 = $('#sec1').html();
      var value2 = $('#sec2').html();
      var value3 = $('#sec3').html();
      var value4 = $('#sec4').html();
      var value5 = $('#sec5').html();
      var value6 = $('#sec6').html();
      var value7 = $('#sec7').html();
      var value8 = $('#sec8').html();
   
    // 0,1,2 
    if(value0 != '' && value1 !='' && value2 !='' && value0 === value1 && value1 === value2 && value2 ===value0) {
      // alert('0,1,2');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value0);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");
      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 0,3,6
    else if(value0 != '' && value3 != '' && value6 != '' && value0 === value3 && value3 === value6 && value6 === value0) { 
      // alert('0,3,6');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value0);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 0,4,8
    else if(value0 != '' && value4 != '' && value8 != '' && value0 === value4 && value4 === value8 && value8 === value0) {    
      // alert('0,4,8');
      win = true;
      var hidden_val = $('#player_val').val();z
      var selected_val = strip_tags(value0);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 1,4,7
    else if(value1 != '' && value4 != '' && value7 != '' && value1 === value4 && value4 === value7 && value7 === value1) {    
      // alert('1,4,7');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value1);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 2,5,8
    else if(value2 != '' && value5 != '' && value8 != '' && value2 === value5 && value5 === value8 && value8 === value2) {    
      // alert('2,5,8');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value2);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 2,4,6
    else if(value2 != '' && value4 != '' && value6 != '' && value2 === value4 && value4 === value6 && value6 === value2) {    
      // alert('2,4,6');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value2);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 3,4,5
    else if(value3 != '' && value4 != '' && value5 != '' && value3 === value4 && value4 === value5 && value5 === value3) {    
      // alert('3,4,5');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value3);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    // 6,7,8
    else if(value6 != '' && value7 != '' && value8 != '' && value6 === value7 && value7 === value8 && value8 === value6) {    
      // alert('6,7,8');
      win = true;
      var hidden_val = $('#player_val').val();
      var selected_val = strip_tags(value6);
      var winning_player = (hidden_val=="p1" && selected_val=="X") ? "p1" : ((hidden_val=="p2" && selected_val=="O") ? "p2" : "");

      if(hidden_val == winning_player) {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You won the game');  
      }
      else {
        $('#game_status_val').val('3');
        $('#game_status').html('Game finished. You lose the game');
      }
      update_winning_status(winning_player);
    }
    else if(value0 != '' && value1 != '' && value2 != '' && value3 != '' && value4 != '' && value5 != '' && value6 != '' && value7 != '' && value8 != ''){
      // alert('die');
      win = true;
      $('#game_status_val').val('3');
      $('#game_status').html('Game tie. Both players played well. Congratulations!');
      update_winning_status("none");
    }
}
  },500);

function strip_tags(string_val) {
  var string = string_val.toString();
  return string.replace(/<\/?[^>]+>/gi, '');
}


function update_winning_status(player_name) {

  $.ajax({

      url : base_url+"game_end",
      type : "POST",
      data : {game_id:game_id,player_name:player_name},
      dataType : "json",
      success : function(res) {
        $.alert("Game completed");
       
      }
    });
}

});// End of read the ready function.
