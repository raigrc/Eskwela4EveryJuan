$(document).ready(function() {
    var baseUrl = window.location.href
    var csrfToken = $('meta[name="csrf-token"]').attr('content'); 

    getTotalEnrolledCourse();

    function getTotalEnrolledCourse() {
        var url = baseUrl + "/overviewNum";

        $.ajax({
            type: "GET",
            url: url,
            success: function(response) {
                console.log(response)
                var enrolleeProgress = response['enrolleeProgress']

                courseProgressGraph(enrolleeProgress);
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    function courseProgressGraph(enrolleeProgress) {

        const statusCounts = {};
        
        enrolleeProgress.forEach((learner) => {
            const status = learner.course_progress;
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        });
    
        const labels = Object.keys(statusCounts);
        const dataValues = labels.map((label) => statusCounts[label]);
    
        const dartmouthGreenPalette = [
            '#00693e',
            '#005230',
            '#004224',
        ];
    
        const ctx = $('#learnerProgressChart');
        if (ctx.data('chart')) {
            ctx.data('chart').destroy();
        }
    
        const newChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: dataValues,
                    backgroundColor: dartmouthGreenPalette,
                    label: 'Number of Learners',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false,
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Status',
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Data Points',
                        },
                    },
                },
            },
        });
    
        ctx.data('chart', newChart);
    }


    $('#courseDetailsBtn').css({
        'background-color': '#FFFFFF',
        'color': '#025C26',
    });

    $('#courseDetailsBtn').on('click', function(e) {
        e.preventDefault();

        $('#courseInfoArea').removeClass('hidden')
        $('#learnersEnrolledArea').addClass('hidden')
        $('#gradesheetArea').addClass('hidden')
        $('#filesArea').addClass('hidden')

        $(this).css({
            'background-color': '#FFFFFF',
            'color': '#025C26',
        });

        $('#learnersEnrolledBtn , #gradesheetBtn , #courseFilesBtn').css({
            'background-color': '#025C26',
            'color': '#ffffff',
        });
    })

    $('#learnersEnrolledBtn').on('click', function(e) {
        e.preventDefault();

        $('#courseInfoArea').addClass('hidden')
        $('#learnersEnrolledArea').removeClass('hidden')
        $('#gradesheetArea').addClass('hidden')
        $('#filesArea').addClass('hidden')
        $(this).css({
            'background-color': '#FFFFFF',
            'color': '#025C26',
        });

        $('#courseDetailsBtn , #gradesheetBtn , #courseFilesBtn').css({
            'background-color': '#025C26',
            'color': '#ffffff',
        });
    })

    $('#gradesheetBtn').on('click', function(e) {
        e.preventDefault();

        $('#courseInfoArea').addClass('hidden')
        $('#learnersEnrolledArea').addClass('hidden')
        $('#gradesheetArea').removeClass('hidden')
        $('#filesArea').addClass('hidden')
        $(this).css({
            'background-color': '#FFFFFF',
            'color': '#025C26',
        });

        $('#courseDetailsBtn , #learnersEnrolledBtn , #courseFilesBtn').css({
            'background-color': '#025C26',
            'color': '#ffffff',
        });
    })

    $('#courseFilesBtn').on('click', function(e) {
        e.preventDefault();

        $('#courseInfoArea').addClass('hidden')
        $('#learnersEnrolledArea').addClass('hidden')
        $('#gradesheetArea').addClass('hidden')
        $('#filesArea').removeClass('hidden')
        $(this).css({
            'background-color': '#FFFFFF',
            'color': '#025C26',
        });

        $('#courseDetailsBtn , #learnersEnrolledBtn , #gradesheetBtn').css({
            'background-color': '#025C26',
            'color': '#ffffff',
        });
    })


    $('#viewDetailsBtn').on('click', function() {

        $('#courseDetailsModal').removeClass('hidden')
    })

    
    $('.closeCourseDetailsModal').on('click', function() {

        $('#courseDetailsModal').addClass('hidden')
    })


    $('#courseEditBtn').on('click', function() {
        $('#courseDetailsEditModal').removeClass('hidden')
    })

    $('.cancelEdit').on('click', function() {
        $('#courseDetailsEditModal').addClass('hidden')
    })

    $('#saveCourseEditDetailsBtn').on('click', function () {
        var updatedCourseName = $('#courseEditName').val();
        var updatedCourseDescription = $('#courseEditDescription').val();
    
        // Remove existing validation spans
        $('.validation-message').remove();
    
        var url = baseUrl + "/editCourseDetails"
    
        if (updatedCourseDescription !== null && updatedCourseDescription !== '' && updatedCourseName !== null && updatedCourseName !== '') {
            $.ajax({
                type: 'POST',
                url: url,
                data: {
                    course_name: updatedCourseName,
                    course_description: updatedCourseDescription,
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function (response) {
                    if (response && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        // Handle success scenario
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                }
            });
        } else {
            // Display validation message for empty course_name
            if (updatedCourseName === null || updatedCourseName === '') {
                $('#courseEditName').before('<span class="validation-message">Please enter a course name.</span>');
            }
    
            // Display validation message for empty course_description
            if (updatedCourseDescription === null || updatedCourseDescription === '') {
                $('#courseEditDescription').before('<span class="validation-message">Please enter a course description.</span>');
            }
        }
    });




    function generatePDF() {
        // Find the content within the specified comments
        var contentHtml = $('#generatedPdfArea').html();
        var courseName = $('#courseNamePdf').text();
        var timestamp = new Date().getTime();
        // Create a new div to hold the content
        var contentDiv = document.createElement('div');
        contentDiv.innerHTML = contentHtml;
    
        // Use html2pdf to generate the PDF
        html2pdf(contentDiv, {
            margin: 10,
            filename: courseName + '_learners_enrolled_' + timestamp +'.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        });
    }
    
    // Event listener for the button click
    $('#generateEnrolledLearnersBtn').click(function () {
        // Call the function to generate and download the PDF
        generatePDF();
    });

        $('#generateGradesheetBtn').click(function () {
            var courseName = $('#courseNamePdf').text();
            var timestamp = new Date().getTime();
        // Specify the table ID or class that you want to export
        $("#gradesheet").table2excel({
            filename: courseName + "_gradesheet_export_"+ timestamp +".xls"
        });
    });


    function generateGradeSheetPDF() {
        // Find the content within the specified comments
        var contentHtml = $('#exportExcelGrades').html();
        var courseName = $('#courseNamePdf').text();
        var timestamp = new Date().getTime();
        // Create a new div to hold the content
        var contentDiv = document.createElement('div');
        contentDiv.innerHTML = contentHtml;
    
        // Use html2pdf to generate the PDF
        html2pdf(contentDiv, {
            margin: 10,
            filename: courseName + '_learners_enrolled_' + timestamp +'.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        });
    }
    
    // Event listener for the button click
    $('#generateGradesheetPDFBtn').click(function () {
        // Call the function to generate and download the PDF
        generateGradeSheetPDF();
    });


    $('#addNewFileBtn').on('click', function() {
        $('#addNewFileModal').removeClass('hidden')
    })

    $('.cancelAdd').on('click', function() {
        $('#addNewFileModal').addClass('hidden')
    })


    $('#deleteCourseBtn').on('click', function() {
        $('#deleteCourseModal').removeClass('hidden')
    })

    
    $('.cancelDelete').on('click', function() {
        $('#deleteCourseModal').addClass('hidden')
    })


    $('#confirmDeleteCourseBtn').on('click', function() {
        var courseID = $(this).data("course-id");
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); // Get the CSRF token from the meta tag

        $.ajax({
            type: 'POST',
            url: '/instructor/course/' + courseID + '/delete/',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                if (response && response.redirect_url) {
                    window.location.href = response.redirect_url;
                } else {
                
                }
            },
            error: function (xhr, status, error) {

                console.log(xhr.responseText);
            }
        });
    })

})