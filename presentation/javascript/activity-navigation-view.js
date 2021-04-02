function checkActivityConditions(elem,courseTag, moduleTag, activityTag = ''){
    // add wait cursor
    elem.classList.add("wait-cursor");
   
    if (!checkActivityConditionsURL){
        checkActivityConditionsWrong(elem,"<div>Erreur en vérifiant les conditions de passage.</div>");
    }
    // test wait 2 seconds
    setTimeout(function (){
        checkActivityConditionsRight(elem,"http://localhost/test_LMS/?BienDemarrerSaTransitionVersLesLogiciels");
    },2000);
}

function checkActivityConditionsWrong(elem,message){
    //remove wait cursor
    elem.classList.remove("wait-cursor");
}

function checkActivityConditionsRight(elem,url){
    // set url
    elem.setAttribute("href",url);
    // remove class
    elem.classList.remove("wait-cursor","disabled");
    // remove events
    elem.removeAttribute("onmouseover");
    elem.addEventListener("click",function (event){
        event.stopPropagation();
        window.location.href = this.getAttribute("href");
    });
}