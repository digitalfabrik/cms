var linesnumber;
var datecompare = [];
var contentcompare = [];
var isActive;
var title = document.title;
var count = 0;

//playing sound
jQuery.extend({
	playSound: function(){
		return jQuery("<embed src='"+arguments[0]+".3' hidden='true' autostart='true' loop='false' class='playSound'>" + "<audio autoplay='autoplay' style='display:none;' controls='controls'><source src='"+arguments[0]+".ogg' /></audio>").appendTo('body');
    }
});

function Chat(){
    this.update = ppUpdateChat;
    this.send = ppSendChat;
	this.getState = getStateOfChat;
	this.initiate = ppInitiateChat;
	this.updateCount = ppUpdateCountNumber;
}

// update lines number
function ppUpdateCountNumber(){
	jQuery.ajax({
		type: "POST",
		data:{
				'function': 'updateCount'
			},
		dataType: "json",
		success: function(data){
			linesnumber = data;
		},
	});
}

//update chat if needed
function getStateOfChat(){
	jQuery.ajax({
		type: "POST",
			   data: {  
			   			'function': 'getState'
			   },
			   dataType: "json",
			   success: function(data){
					if(data != null){
							idnumber = data;
							if(idnumber != linesnumber){
								ppUpdateChat();
							}
					}
			   },
	});
	jQuery(window).blur(function(){
		isActive = false;
	});
	jQuery(window).focus(function(){
		isActive = true;
	});
	if(isActive == true){
		document.title = title;
		count = 0;
	}
}

//send the message
function ppSendChat(message, nickname, email, site){
	jQuery.ajax({
		type: "POST",
		data:{
				'function': 'send',
				'message': message,
				'nickname': nickname,
				'email': email,
				'site': site
			},
		dataType: "json",
		success: function(data){
			ppUpdateChat();
		},
	});
}

//updates the chat
function ppUpdateChat(){
	     jQuery.ajax({
			   type: "POST",
			   data: {  
			   			'function': 'update'
						},
			   dataType: "json",
			   success: function(data){
				   if(data != null){
						for (var i = 0; i < data.result1.length; i++){
							if(jQuery.inArray(data.result2[i], contentcompare) > -1 && jQuery.inArray(data.result3[i], datecompare) > -1){
                            	continue;
                            }else{
								jQuery('#chat-area').append(jQuery("<p style='border-bottom: 0px;'>" + "<span id='group' style='background:#" + data.result6[i]+"'>" + data.result5[i]+":" + "</span>" + "<span id='nick' style='background:#"+data.result6[i]+"'>" + data.result1[i] + "</span>" + "<span id='email' style='background:#"+ data.result6[i]+"'>("+data.result4[i]+")</span>"+ "<span id=\"date\">" + data.result3[i] + "</span>" +"<p style='border-bottom: 0px'></p>"+"<b>"+ data.result2[i]+"</b>" + "</p>"));
								if(isActive == false){
									count++;
									document.title = "(" + count + ")" + title;
									jQuery.playSound('/../wp-content/plugins/author-chat/notifyauthorchat'); //url not working on localhost
							   }
							}
                        datecompare.push(data.result3[i]);
						contentcompare.push(data.result2[i]);
                        }
				   }
				   document.getElementById('chat-area').scrollTop = document.getElementById('chat-area').scrollHeight;
				   ppUpdateCountNumber();
			   },
			});
}

function ppInitiateChat(){
	     jQuery.ajax({
			   type: "POST",
			   data: {  
			   			'function': 'initiate'
					 },
			   dataType: "json",
			   success: function(data){
				   if(data != null){
						for (var i = 0; i < data.result1.length; i++){
                            jQuery('#chat-area').append(jQuery("<p style='border-bottom: 0px'>" + "<span id='group' style='background:#"+data.result6[i]+"'>" + data.result5[i]+":" + "</span>" + "<span id='nick' style='background:#"+data.result6[i]+"'>" + data.result1[i] + "</span>" + "<span id='email' style='background:#"+data.result6[i]+"'>("+data.result4[i]+")</span>" +"<span id=\"date\">" + data.result3[i] + "</span>" +"<p style='border-bottom: 0px'></p>" + "<b>"+data.result2[i]+"</b>" + "</p>"));
                            datecompare.push(data.result3[i]);
							contentcompare.push(data.result2[i]);
                        }
				   }
				   document.getElementById('chat-area').scrollTop = document.getElementById('chat-area').scrollHeight;
				   ppUpdateCountNumber();
			   },
			});
}
