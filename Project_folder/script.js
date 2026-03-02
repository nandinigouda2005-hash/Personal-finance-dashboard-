const signupbutton=document.getElementById('signupbutton');
const signinbutton=document.getElementById('signinbutton');
const signinform=document.getElementById('sign in');
const signupform=document.getElementById('sign up');

signupbutton.addEventListener('click',function(){
    signinform.style.display="none";
    signupform.style.display="block";
})
signinbutton.addEventListener('click',function(){
    signinform.style.display="block";
    signupform.style.display="none";
})