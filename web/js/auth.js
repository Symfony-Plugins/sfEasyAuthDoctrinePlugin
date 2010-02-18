var link, form;

if(link = document.getElementById('pwResetLink'))
{
  // on click...
  link.onclick = function()
  {
    link.className='hidden';
  
    if (form = document.getElementById('pwResetForm'))
    {
      form.className = form.className.replace(/hidden/, '');
    }
  }
}
