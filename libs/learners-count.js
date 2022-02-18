function updateLearnersCounts(event, tabres){
    let $dashboards = $('.panel-lms-dashboard');

    $dashboards.each(function(){
        var finishedLearnersNb = $(this).find('.finished-learners-group table tr.bazar-entry:not([style*="display: none;"])').length;
        var unfinishedLearnersNb = $(this).find('.unfinished-learners-group table tr.bazar-entry:not([style*="display: none;"])').length;

        let $ratio = $(this).find('.finished-ratio');
        $ratio.find('.learners-finished').html(finishedLearnersNb);
        $ratio.find('.learners-total').html(finishedLearnersNb + unfinishedLearnersNb);
    });
}

$('body').on("updatedfilters", updateLearnersCounts);