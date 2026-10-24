document.querySelectorAll('form[data-workflow]').forEach(form=>{
  form.addEventListener('submit',event=>{
    if(!form.checkValidity())return;
    const button=form.querySelector('button[type="submit"]');
    if(button){setTimeout(()=>{if(event.defaultPrevented)return;button.disabled=true;button.textContent='Saving…';},0);}
  });
});
document.querySelector('[data-workspace]')?.addEventListener('change',event=>{if(event.target.value)location.href=event.target.value;});
document.querySelector('[data-demo-account]')?.addEventListener('change',event=>{
  const email=document.querySelector('[name="email"]');if(email)email.value=event.target.value;
});
