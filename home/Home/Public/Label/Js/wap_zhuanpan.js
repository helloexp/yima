var page = 1;
$(document).ready(function(e) {
	$("#wrapper,.loading").on("touchmove",function(){
		event.preventDefault();
	});
	$(".music").on("touchstar click",function(){
		var audio = document.getElementById('audio');
		if(audio.paused){
			audio.play();
			$(".music").addClass("on");
		}else{
			audio.pause();
			$(".music").removeClass("on");
		}
	});
	$(".chocolate,.container .t5").on("touchstar mousedown",function(){
		setTimeout(function(){
			Hotrecommendt.swipeNext();
		},200)
	});
	$(".container2 .t5").on("touchstar mousedown",function(){
		setTimeout(function(){
			Hotrecommendt.swipeNext();
		},200)
	});
	$(".activityMsg").on("touchstar mousedown",function(){
		$(".container4-con").css("transform","translate3d(0px,0,0)");
	});
	setTimeout(function(){
		$("#wrapper").addClass("begin")
	},1000);
	init();
	readyAction();
});
function btnlogin(){
		var w=$("#main").width();
		$(".container4-con").css("transition","-webkit-transform 1s ease-out");
		$(".container4-con").css("transform","translate3d(-"+w+"px,0,0)");
}

function init(){
	var w=$("#main").width();
	var h=$("#main").height();
	var scale=w/640*2;
	$(".title.t1").css({
		height:94*scale
	});
	$(".title.t2").css({
		height:78*scale
	});
	$(".chocolate").css({
		height:163*scale
	});
	$(".ind-logo").css({
		height:66*scale
	});
	$(".activity-logo").css({
		height:76*scale
	});
}

function readyAction(){
    var arry=[1,2,3,4,5,6];
	var arryname=["Johnny Moo<br>情人节套餐","DIY<br>自制巧克力","玫瑰花<br>一朵","达尔牧场<br>情人节套餐","充寿司日本料理<br>情人节套餐","拍立得<br>情侣照"];
    var probability=100;
    var lotCon="";
    var lotTxt="";
    var lotImg="";
    var arry_i=0;
    var lotCon_i=0;
    if(arry.length<=3){
    	if(arry.length==1){
    	    lotTxt="<p class='txt1 prize1' data-id='"+arry[arry_i]+"><span>"+arryname[arry_i]+"</span></p><p class='txt2'><span>未中奖</span></p><p class='txt3'><span>未中奖</span></p>";
        }else{
            for(var i=1;i<=arry.length*2;i++){
                if(i%2){
                    lotTxt=lotTxt+"<p class='txt"+i+" prize"+i+"' data-id='"+arry[arry_i]+"'><span>"+arryname[arry_i]+"</span></p>";
                    arry_i++;
                }else{
                    lotTxt=lotTxt+"<p class='txt"+i+" prize"+i+"' data-id='"+arry[arry_i]+"'><span>未中奖</span></p>";
                }
            }
        }
    }else{
        for(var i=1;i<=arry.length;i++){
            lotTxt=lotTxt+"<p class='txt"+i+"' data-id='"+arry[arry_i]+"'><span>"+arryname[arry_i]+"</span></p>";
            arry_i++;
        }
        if(probability!=100){
            lotCon=lotCon+"<div class='prize prize"+i+"' data-id='noprize'><div class='prizebg'></div></div>";
            lotTxt=lotTxt+"<p class='txt"+i+" prize"+i+"'><span>未中奖</span></p>";
        }
    }
    $(".lotTxt").append(lotTxt);
    loadAction();
}
function loadAction(){
    var Pnum=$(".lotTxt p").length;
    var deg=360/Pnum;
    for(var i=1;i<=Pnum;i++){
        var numdeg=deg*i-deg;
        if(Pnum==8){var pdeg1=i*deg+23;};
        if(Pnum==7){var pdeg1=i*deg+65;};
        if(Pnum==6){var pdeg1=i*deg+deg;};
        if(Pnum==5){var pdeg1=i*deg+54;};
        if(Pnum==4){var pdeg1=i*deg+45;};
        if(Pnum==3){var pdeg1=i*deg+deg+30;};
        if(Pnum==2){var pdeg1=i*deg+deg;};
        var pdeg2=90-deg;
        $(".txt"+i).css("transform","rotate("+numdeg+"deg)");
    }
}
function haveAction(id,type){
	var classid=$(".lotTxt [data-id='"+id+"']").index()+1;
	$(".lottery-msg .prize-title"+id).show();
	setTimeout(function(){
		$(".msg"+type).show();
		$("#lottery").addClass("an");
		$(".btn-getlottery").show();
	},200)
	setTimeout(function(){
		$(".lotCenter").hide();
	},500)
}

