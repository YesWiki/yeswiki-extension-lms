function checkActivityConditions(elem,courseTag, moduleTag, activityTag = ''){
    // add wait cursor
    elem.classList.add("wait-cursor");

    // test wait 2 seconds
    setTimeout(function (){
        // false case
        // remove wait cursor
        // elem.classList.remove("wait-cursor");
        //right case
        // set url
        elem.setAttribute("href","http://localhost/test_LMS/?BienDemarrerSaTransitionVersLesLogiciels");
        // remove class
        elem.classList.remove("wait-cursor","disabled");
        // remove events
        elem.removeAttribute("onmouseover");
        elem.addEventListener("click",function (event){
            event.stopPropagation();
            window.location.href = this.getAttribute("href");
        });
    },2000);
}