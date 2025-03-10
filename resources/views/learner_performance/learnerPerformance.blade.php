@include('partials.header')
<section class="flex flex-row w-full h-screen text-sm bg-mainwhitebg md:text-base lg:h-screen">


@include('partials.learnerSidebar')

<section class="w-full px-2 pt-[100px] mx-2 mt-2 md:overflow-auto md:w-3/4 lg:w-9/12">
    <div  class="p-3 pb-4 overflow-auto bg-white rounded-lg shadow-lg overscroll-auto">
        <a href="{{ url("/learner/dashboard") }}" class="my-2 bg-gray-300 rounded-full ">
            <svg  xmlns="http://www.w3.org/2000/svg" height="30" viewBox="0 -960 960 960" width="24"><path d="M560-240 320-480l240-240 56 56-184 184 184 184-56 56Z"/></svg>
        </a>
        <h1 class="mx-5 text-2xl font-semibold md:text-3xl">PERFORMANCE DASHBOARD</h1>
        <hr class="border-t-2 border-gray-300 my-6">

        <div class="mt-5 p-10 flex" id="genInfo">
            <div class="w-3/5 h-[300px] border-2 border-darthmouthgreen" id="totalCourseArea">
                <div class=" mt-10 mx-10 h-2/3 text-center item-center flex justify-center">
                    <i class="fa-solid fa-book-open-reader text-darthmouthgreen text-[175px]"></i>
                    <p class="font-bold mt-3 py-14 mx-5 text-2xl"><span class="text-darthmouthgreen text-[125px]" id="totalCourseNum">0</span><br>Total Courses Enrolled</p>
                </div>
                <div class="flex mt-5 justify-center">
                    <div class="flex items-center mx-5">
                        <div class="rounded-full w-3 h-3 mx-3 bg-darthmouthgreen"></div>
                        <p class="font-bold text-md">Approved: <span id="totalApprovedCourse" class="">0</span></p>
                    </div>

                    <div class="flex items-center mx-5">
                        <div class="rounded-full w-3 h-3 mx-3 bg-yellow-400"></div>
                        <p class="font-bold text-md">Pending: <span id="totalPendingCourse" class="">0</span></p>
                    </div>

                    <div class="flex items-center mx-5">
                        <div class="rounded-full w-3 h-3 mx-3 bg-red-700"></div>
                        <p class="font-bold text-md">Rejected: <span id="totalRejectedCourse" class="">0</span></p>
                    </div>
                </div>
                
            </div>
            <div class="h-[300px] w-2/5 ml-5 border-2 py-5 border-darthmouthgreen flex flex-col justify-between items-center" id="enrolledLearnerSyllabusCompletionCount">
                <div class="py-5 mt-10 ml-10 flex items-center">
                    <i class="fa-solid fa-book-bookmark text-darthmouthgreen text-[100px]"></i>
                    <p class="font-bold text-md px-8 pt-5 text-center"><span class="text-darthmouthgreen text-[75px]" id="totalSyllabusCompletedCount">0</span><br>Topics Completed</p>
                </div>

                <div class="flex justify-between">
                    <div class="">
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-file text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Total Lessons: <span id="totalLessonsCount" class="">0</span></p>
                        </div>
    
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-clipboard text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Total Activities: <span id="totalActivitiesCount" class="">0</span></p>
                        </div>
    
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-pen-to-square text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Total Quizzes: <span id="totalQuizzesCount" class="">0</span></p>
                        </div>
                    </div>
    
                    <div class="">
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-file text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Completed: <span id="totalLessonsCompletedCount" class="">0</span></p>
                        </div>
    
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-clipboard text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Completed: <span id="totalActivitiesCompletedCount" class="">0</span></p>
                        </div>
    
                        <div class="flex items-center mx-1">
                            <i class="fa-solid fa-pen-to-square text-darthmouthgreen text-xl mx-3"></i>
                            <p class="font-bold text-md">Completed: <span id="totalQuizzesCompletedCount" class="">0</span></p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <hr class="border-t-2 border-gray-300 my-6">

        <div class="w-full p-10" id="perCourseArea">
            <select name="" class="w-full text-lg px-5 py-3" id="perCourseSelectArea">
                <option value="ALL" selected>ALL COURSES</option>
                @foreach ($courseData as $course)
                    <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                @endforeach
            </select>

            <div class="mt-5 w-full flex" id="perCourseInfoArea">
                <div class="w-1/2 h-[350px] border-2 border-darthmouthgreen p-5" id="courseInfo"></div>

                <div class="w-1/2 h-[350px] ml-5 border-2 border-darthmouthgreen" id="courseGraph">
                    <canvas id="courseDataChart"></canvas>
                </div>
            </div>
        </div>

        <hr class="border-t-2 border-gray-300 my-6">

        <div class="w-full p-10" id="courseListArea">
            <h1 class="text-2xl mb-5 font-semibold text-black">List of Enrolled Courses</h1>
            <table class="rounded-xl">
                <thead class="bg-darthmouthgreen py-3 text-white text-xl">
                    <th class="w-1/5">Course Name</th>
                    <th class="w-1/5">Course Code</th>
                    <th class="w-1/4">Instructor</th>
                    <th class="w-1/5">Status</th>
                    <th class="w-1/5">Date Started</th>
                    <th class="w-1/5"></th>
                </thead>

                <tbody class="rowCourseDataArea mt-5">
            
                </tbody>
            </table>
        </div>

        <hr class="border-t-2 border-gray-300 my-6">

        <div class="flex justify-between">
            <h1 class="mx-5 text-2xl font-semibold">Your session data</h1>
        </div>

        <div class="mt-5 flex justify-center" id="learnerSessionDataArea">
            <div class="mx-5 w-11/12 h-[350px] border-2 border-darthmouthgreen rounded-xl" id="learnerSessionGraphArea">
                <canvas id="learnerSessionGraph"></canvas>
            </div>
        </div>


    </div>
</section>

@include('partials.learnerProfile')
</section>
@include('partials.footer')