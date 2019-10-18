
parent = window.sessionStorage.getItem('parent');
son = window.sessionStorage.getItem('son');
console.log('parent'+parent);
console.log('son'+son);
$(document).ready(function(){
	parent = parent-1;
	$('.dropdown').eq(parent).addClass('collapse-open open');
	$('.open .dropdown-menu li').eq(son).children('a').children('span').addClass('son_active');
})
