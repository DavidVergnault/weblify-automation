jQuery(function() {

    const progressBase = document.getElementById('progress__base');
const progressBar = document.getElementById('progress__bar');
const progressPoints = Array.from(progressBase.children).slice(1);

progressBase.onclick = function(e) {
  const target = e.target;
  
  if (!target.classList.contains('progress__bullet')) {
    // Ignore clicks on #progress__base and #progress__bar
    return;
  }
  
  const index = progressPoints.indexOf(target);
  
  progressBar.style.width = `${ 100 * index / (progressPoints.length - 1) }%`;
  
  // Add the active class to all the ones that come before the
  // clicked one, and the clicked one itself:
  for (let i = 0; i <= index; ++i) {
    progressPoints[i].classList.add('active');
  }
  
  // Remove the active class from all the ones that come after
  // the clicked one:
  for (let i = index + 1; i < progressPoints.length; ++i) {
    progressPoints[i].classList.remove('active');
  }
};

});