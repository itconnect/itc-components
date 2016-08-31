(function(){
	APS = {
		'storedData': {
			'branch': null,
			'values': {

			}
		},
		addListeners: function () {
			// Listen for initilization
			$('.aps-init').click(function(){
				// Check for previously stored data
				if (localStorage.APS) {
					APS.changeSlide('aps-restart');
				} else {
					// Create new object which gets stored in localStorage
					APS.createStorage();
					// Chage to the first slide
					APS.changeSlide($(this).data('aps-goto'));
				}
				// store the BRANCH, then store values based off of it {branch: aca, values: [b,c,a]}
				// if branch is changed/set, delete all data. essentially a reset
				
			});

			// Add listeners to all option buttons
			$('.aps-option').each(function(){ 
				$(this).on('click', function(){
					if ($(this).data('aps-branch')) {
						// store the value in APS.storedData then update local storage
						APS.storedData['branch'] = $(this).data('aps-branch');
						APS.updateStorage();
					} else if ($(this).closest('.slide').data('aps-slide').length) {
						// store the vlaue in APS.storedData then update local storage
						//APS.storedData.values[$(this).closest('.slide').data('aps-slide')] = $(this).data('aps-value');
						console.log('this is a slide data');
						APS.storedData.values[0] = 'a';
						APS.updateStorage();
					}
					console.log($(this).closest('.slide').data('aps-slide'));
					APS.changeSlide($(this).data('aps-goto'));
				});
			});

			// Restart the process
			$('.aps-restart').each(function(){
				$(this).on('click', function(){
					if ($(this).data('aps-restart') == 'yes') {
						if (window.confirm("Are you sure you want to start over?")) { 
							APS.createStorage();
							APS.changeSlide('aps-1');
						} 
					} else {
						alert('no code here yet');
					}
				});
			});
		},
		changeSlide: function(slide) {
			$('.aps .slide').each(function(){
				$(this).fadeOut(400);
			});
			$('#' + slide).fadeIn(400);
			
		},
		createStorage: function() {
			// Stores data in local storage if possible
			if (this.storageAvailable('localStorage')) {
				localStorage.setItem("APS",JSON.stringify(APS.storedData));
			} 
		},
		updateStorage: function() {
			// Writes info storedin APS.storedData to localStorage as a string
			if (this.testJSON()) {
				localStorage.setItem('APS',JSON.stringify(APS.storedData));
			}
		},
		retrieveStorage: function() {
			// Grabs data from local storage, sends to parser and writes to APS.storedData
			if (this.testJSON()) {
				APS.storedData = JSON.parse(localStorage.getItem('APS')); 
			}
		},
		storageAvailable: function (type) {
			try {
				var storage = window[type],
					x = '__storage_test__';
				storage.setItem(x, x);
				storage.removeItem(x);
				return true;
			}
			catch(e) {
				return false;
			}
		},
		testJSON: function() {
			if (typeof JSON === 'object' && typeof JSON.parse === 'function') {
			    return true;
			}
			return false;
		},
		init: function() {
			// Add listeners to all slide options
			this.addListeners();
		}
	}


	$(window).load(function() {
		APS.init();
	});
})();