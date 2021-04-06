function checkActivityNavigationConditions(elem,courseTag, moduleTag, activityTag = ''){
    // add wait cursor
    elem.classList.add("wait-cursor");
   
    if (!checkActivityNavigationConditionsURL){
        checkActivityNavigationConditionsWrong(elem,"<div>Erreur en vérifiant les conditions de passage.</div>");
    }
    // test wait 2 seconds
    setTimeout(function (){
        checkActivityNavigationConditionsRight(elem,"http://localhost/test_LMS/?BienDemarrerSaTransitionVersLesLogiciels");
    },2000);
}

function checkActivityNavigationConditionsWrong(elem,message){
    //remove wait cursor
    elem.classList.remove("wait-cursor");
}

function checkActivityNavigationConditionsRight(elem,url){
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