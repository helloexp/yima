
//性别
var g_aGenderManBtn=[];
var g_aGenderWomanBtn=[];
var g_aInputGender=[];
var g_iGenderMaxIndex=0;

function setGenderSelector(oGenderManElement, oGenderWomanElement, oInputGender)
{
	g_aGenderManBtn.push(oGenderManElement);
	g_aGenderWomanBtn.push(oGenderWomanElement);
	g_aInputGender.push(oInputGender);
	
	oInputGender.value='male';	//female
	oGenderManElement.className='men_active';
	oGenderWomanElement.className='woman_normal';
	
	oGenderManElement.index=g_iGenderMaxIndex;
	oGenderWomanElement.index=g_iGenderMaxIndex;
	
	g_iGenderMaxIndex++;
	
	oGenderManElement.onmousedown=__genderManBtnClickHandler__;
	oGenderWomanElement.onmousedown=__genderWomanBtnClickHandler__;
}

function __genderManBtnClickHandler__(ev)
{
	if(this.className === 'men_normal')
	{
		this.className='men_active';
		g_aGenderWomanBtn[this.index].className='woman_normal';
		g_aInputGender[this.index].value='male';
                document.getElementById('head_img').src = './Home/Public/Label/Image/wap_ebc/a_man.png?v=__VR__';
	}
}

function __genderWomanBtnClickHandler__(ev)
{
	if(this.className === 'woman_normal')
	{
		this.className='woman_active';
		g_aGenderManBtn[this.index].className='men_normal';
		g_aInputGender[this.index].value='female';
                document.getElementById('head_img').src = './Home/Public/Label/Image/wap_ebc/a_woman.png?v=__VR__';
                
	}
}


window.onload=function ()
{	
	var oBtnGenderMan=document.getElementById('gender_man');
	var oBtnGenderWoman=document.getElementById('gender_woman');
	var oInputGender=document.getElementById('gender');
	setGenderSelector(oBtnGenderMan, oBtnGenderWoman, oInputGender);
}