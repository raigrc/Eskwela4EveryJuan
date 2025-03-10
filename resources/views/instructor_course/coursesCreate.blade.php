@include('partials.header')

<section class="flex flex-row w-full h-screen bg-mainwhitebg">
    @include('partials.instructorNav')
    @include('partials.instructorSidebar')
    {{-- MAIN CONTENT --}}
    <section class="w-full px-2 pt-28 md:overflow-auto md:w-3/4 lg:w-9/12 md:pt-20">
        <div class="flex flex-row items-center mb-4">
            <a href="{{ url('/instructor/courses') }}" class="w-8 h-8 m-2">
                <svg xmlns="http://www.w3.org/2000/svg" height="25" viewBox="0 -960 960 960" width="24"><path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/></svg>
            </a>
        </div>

        <h1 class="text-6xl font-semibold">Create New Course</h1>
        <form action="" id="addCourse" name="addCourse" class="px-2">
            @csrf
            {{-- FIRST HALF --}}
            <div id="firstCreateCourse" class="mx-10 mt-10">
                <div class="flex flex-col my-2">
                    <label for="course_name" class="text-2xl">Course Name</label>
                    <input class="px-10 py-5 mt-5 text-xl font-semibold border-2 rounded-lg border-darthmouthgreen" id="course_name" name="course_name" type="text">
                </div>
                <div class="flex flex-col my-2 mt-5">
                    <label for="course_description" class="text-2xl">Description</label>
                    <textarea class="h-[200px] max-h-[200px] px-10 py-5 mt-5 text-xl font-regular border-2 rounded-lg border-darthmouthgreen" name="course_description" id="course_description"></textarea>
                </div>
                <div class="flex flex-col my-2 mt-5">
                    <label for="course_difficulty" class="text-4xl">Course Difficulty</label>
                    <select class="px-10 py-2 mt-5 text-lg font-semibold border-2 rounded-lg border-darthmouthgreen" name="course_difficulty" id="course_difficulty" >
                        <option value="" selected>--select an option--</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>

                <div class="flex justify-end w-full">
                    <button id="nextAddCourse" class="px-10 py-3 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Next</button>
                </div>
                
            </div>
            

            {{-- SECOND HALF --}}
            <div class="hidden" id="secondCreateCourse">
                <h1 class="mt-8 text-4xl font-semibold">Initial set up of Syllabus</h1>
                <p>initial setup of the course syllabus, you can change it later</p>
                
                <div id="lessonContainer" class="mt-10">
                    <table class="w-full text-sm text-left">
                        <thead class="h-8 text-xl text-center uppercase bg-darthmouthgreen text-mainwhitebg">
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th></th>
                        </thead>
                        <tbody id="lesson_body" class="">

                                {{-- <tr class="border-b-2 border-black">
                                    <td class="text-center">1</td>
                                    <td class="flex items-center justify-between">
                                        <input type="text" disabled value="Lesson 1">
                                        <div class="">
                                            <button class="h-10 px-2 mx-2 my-10 font-medium rounded cursor-pointer bg-amber-400" id="edit-lesson">Edit</button>
                                            <button class="h-10 px-2 mx-2 my-10 font-medium rounded cursor-pointer bg-amber-400" id="delete-lesson">Delete</button>
                                        </div>
                                    </td>
                                </tr> --}}

                                

                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-center">
                    

                    <button id="addLesson_start" class="px-10 py-3 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Add New</button>

                </div>          
                
                <div class="flex justify-end w-full">
  
                    <div class="flex justify-end w-full">
                        <button id="prevAddCourse1" type="button" class="px-10 py-3 mx-1 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Prev</button>
                        <button id="nextAddCourse2" type="button" class="px-10 py-3 mx-1 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Next</button>
                    </div>
    
                </div>
                
            </div>

                        {{-- THIRD PART --}}
                        <div class="hidden" id="thirdCreateCourse">
                            <h1 class="mt-8 text-4xl font-semibold">Upload your Module Here</h1>
                            <p>upload your course module here, it will help in setting the learner chatbot</p>
                            
                            <div class="flex flex-col my-2">
                                <input id="courseFilesUpload" class="mt-5 text-lg font-semibold bg-white border-2 rounded-lg border-darthmouthgreen file:px-5 file:py-3 file:bg-darthmouthgreen file:text-white" type="file" name="file" id="fileInput" multiple>
                                <ul class="px-2 py-2" id="uploadedFileName">
                                </ul>
                            </div>
     
                            
                            <div class="flex justify-end w-full">
              
                                <div class="flex justify-end w-full">
                                    <button id="prevAddCourse2" type="button" class="px-10 py-3 mx-1 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Prev</button>
                                    <button id="submitFiles" type="submit" class="px-10 py-3 mx-1 mt-5 text-xl font-semibold text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Create New Course</button>
                                </div>
                
                            </div>
                            
                        </div>
        </form>
    </section>

                <div id="selectTypeParent" class="fixed top-0 left-0 z-50 flex items-center justify-center hidden w-screen h-screen rounded shadow-lg bg-grey-400 backdrop-blur-sm" aria-hidden="true" >
                    <div id="selectTypeChild" class="relative h-auto p-10 pt-8 m-auto mx-4 bg-white shadow-lg rounded-xl" >
                        
                        <h2 class="mb-2 text-2xl font-semibold">Choose Category</h2>
                        <div class="flex flex-col items-center">
                            <label class="my-2 text-white" for="">Select one</label>
                            <select class="max-w-xs px-5 py-3 text-xl border-2 select select-bordered border-darthmouthgreen" name="add_category" id="modal_add_category">
                                <option value="" disabled selected>--Select here--</option>
                                <option value="LESSON">Lesson</option>
                                <option value="QUIZ">Quiz</option>
                                <option value="ACTIVITY">Activity</option>
                            </select>
                        </div>
                        
                        <div class="flex flex-row items-center justify-center m-4 text-white">

      
                            <button id="selectTypeConfirmBtn" type="button" class="px-10 py-3 mx-1 mt-5 text-xl text-white bg-darthmouthgreen rounded-xl hover:border-2 hover:border-darthmouthgreen hover:text-darthmouthgreen hover:bg-white">Add</button>
                            <button id="selectTypeCloseBtn" type="button" class="px-10 py-3 mx-1 mt-5 text-xl text-white bg-gray-500 rounded-xl hover:border-2 hover:border-gray-500 hover:text-gray-500 hover:bg-white">Cancel</button>
                
                        </div>
                        
                    </div>
                </div>      
    
    @include('partials.instructorProfile')
</section>

@include('partials.footer')