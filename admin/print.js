

$().ready(function()
{
	pr_init_links(); 
	pr_do();
});



function pr_do()
{
	if(window.print) {
		window.print();
	}
	
	return false;
}


function pr_init_links()
{
	for( i = 0; i < document.links.length; i++ ) {
		document.links[i].onclick = new Function('return pr_do();');
	}
	
	for( i = 0; i < document.forms.length; i++ ) {
		document.forms[i].onsubmit = new Function('return pr_do();');
	}
}


