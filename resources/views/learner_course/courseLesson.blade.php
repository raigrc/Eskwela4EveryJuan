@include('partials.header')
<section class="flex flex-row w-full h-screen text-sm bg-mainwhitebg md:text-base lg:h-screen">


@include('partials.learnerSidebar')

<section class="w-full px-2 pt-[100px] mx-2 mt-2 md:overflow-auto md:w-3/4 lg:w-9/12">
    <div  class="p-3 pb-4 overflow-auto bg-white rounded-lg shadow-lg overscroll-auto">
        <div style="background-color:{{$mainBackgroundCol}};" class="z-50 p-2 text-white rounded-xl">
            <a href="{{ url("/learner/course/manage/$syllabus->course_id/overview") }}" class="my-2 bg-gray-300 rounded-full ">
                <svg  xmlns="http://www.w3.org/2000/svg" height="30" viewBox="0 -960 960 960" width="24"><path d="M560-240 320-480l240-240 56 56-184 184 184 184-56 56Z"/></svg>
            </a>
            <h1 class="w-1/2 py-4 text-5xl font-bold"><span class="">{{ $syllabus->course_name }}</span></h1>
            {{-- subheaders --}}
            <div class="flex flex-col fill-mainwhitebg">
                <h1 class="w-1/2 py-4 text-4xl font-bold"><span class="">{{ $syllabus->lesson_title }}</span></h1>
            </div>
        </div> 
        
        {{-- main content --}}
        <div class="px-2">
            <div class="mt-1 text-gray-600 text-l">
                <a href="{{ url('/learner/courses') }}" class="">course></a>
                <a href="{{ url("/learner/course/$syllabus->course_id") }}">{{$syllabus->course_name}}></a>
                <a href="{{ url("/learner/course/manage/$syllabus->course_id/overview") }}">content></a>
                <a href="">{{ $syllabus->lesson_title }}</a>
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between pb-3 my-4 mt-5 border-b-2 border-seagreen">
                    <div class="relative flex items-center">
                        <svg class="absolute left-0 border-2 border-black rounded-full p-[2px]" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 32 32"><path fill="currentColor" d="M19 10h7v2h-7zm0 5h7v2h-7zm0 5h7v2h-7zM6 10h7v2H6zm0 5h7v2H6zm0 5h7v2H6z"/><path fill="currentColor" d="M28 5H4a2.002 2.002 0 0 0-2 2v18a2.002 2.002 0 0 0 2 2h24a2.002 2.002 0 0 0 2-2V7a2.002 2.002 0 0 0-2-2ZM4 7h11v18H4Zm13 18V7h11v18Z"/></svg>
                    
                        {{-- <h1 class="pl-[50px] text-3xl font-bold">{{ $lessonInfo->lesson_title }}</h1> --}}
                        {{-- <input class="ml-[50px] w-[750px] text-3xl font-bold border-none" disabled type="text" name="lesson_title" id="lesson_title" value="{{ $syllabus->lesson_title }}"> --}}
                        <div id="lesson_title" class="pl-[50px] text-3xl font-bold border-none" contenteditable="false">{{ $syllabus->lesson_title }}</div>
                    </div>
                </div> 
            </div>

            {{-- course --}}
            <div class="mt-5">
                @if ($syllabus->picture !== null)
                <div id="lesson_img" class="flex justify-center w-full h-[400px] my-4 rounded-lg shadow">
                    <div class="w-full h-[400px] overflow-hidden rounded-lg">
                        <img src="{{ asset("storage/$syllabus->picture") }}" class="object-contain w-full h-full" alt="">
                    </div>
                </div>
                
                @else
                    
                @endif
            </div>

             {{-- lesson content area --}}
             <div id="main_content_area" class="">
                @forelse ($lessons as $lesson)
                <div data-content-order="{{$lesson->lesson_content_order}}" class="w-full px-10 my-2 mb-8 lesson_content_area">
                   
                  
                    @if ($lesson->picture !== null)
                        <img src="{{ asset("storage/$lesson->picture") }}" class="object-contain w-full h-full" alt="">
                    @else
                    @endif
                    <div class="contentArea text-xl font-normal lesson_content_input_disp mt-5 px-5" style="white-space: pre-wrap">{!! $lesson->lesson_content !!}</div>
                     
                    {{-- <p class="w-[80%] max-w-full min-w-full text-xl lesson_content_input_disp" style="white-space: pre-wrap">{{$lesson->lesson_content}}</p>
                    <textarea name="lesson_content_input" id="" class="hidden text-xl lesson_content_input w-[80%] min-w-[80%] max-w-[80%] h-[120px] resize-none" disabled>{{ $lesson->lesson_content }}</textarea> --}}
                    @if ($lesson->video_url !== null)
                        <div id="lesson_content_url" class="flex justify-center w-full h-[400px] my-4 rounded-lg shadow">
                            <div class="url_embed_area w-full h-[400px] flex justify-center overflow-hidden rounded-lg">
                                {!! $lesson->video_url !!}
                            </div>
                        </div>    
                    @else
                    @endif
                </div>
                @empty
                <div class="my-2 mb-8">
                    {{-- <h1 class="text-lg font-medium">What is business?</h1> --}}
                    <p class="pl-4 text-justify">No Lesson content</p>
                </div>
                @endforelse
            </div>
            
            
            


            <div class="px-10 mt-[50px] flex justify-between">
                <a href="{{ url("/learner/course/manage/$syllabus->course_id/overview") }}" class="flex justify-center w-1/2 py-3 mx-3 text-xl font-semibold text-white bg-darthmouthgreen hover:bg-green-900 rounded-xl">
                    Return    
                </a>
                <button type="button" id="finishLessonBtn" data-course-id="{{$syllabus->course_id}}" data-learner-course-id="{{$syllabus->learner_course_id}}" data-syllabus-id="{{$syllabus->syllabus_id}}" class="flex justify-center w-1/2 py-3 mx-3 text-xl font-semibold text-white bg-darthmouthgreen hover:bg-green-900 rounded-xl">
                    Finish
                </button>         
            </div>
        




        </div>
        

        <div id="finishLessonModal" class="fixed top-0 left-0 flex items-center justify-center hidden w-full h-full bg-gray-200 bg-opacity-75 modal">
            <div class="modal-content bg-white p-4 rounded-lg shadow-lg w-[500px]">
                <div class="flex justify-end w-full">
                    <button class="cancelFinishLessonBtn">
                        <i class="text-xl fa-solid fa-xmark" style="color: #949494;"></i>
                    </button>
                </div>

                <h2 class="mb-2 text-xl font-semibold">Do you wish to proceed to the next topic?</h2>

                <div class="flex justify-center w-full mt-5">
                    <button data-course-id="{{$syllabus->course_id}}" data-learner-course-id="{{$syllabus->learner_course_id}}" data-syllabus-id="{{$syllabus->syllabus_id}}" id="confirmFinishLessonBtn" class="px-4 py-2 mx-2 mt-4 text-white rounded-lg hover:bg-green-950 bg-seagreen hover:bg-darkenedColor">Confirm</button>
                    <button id="" class="px-4 py-2 mx-2 mt-4 text-white bg-red-500 rounded-lg hover:bg-red-900 cancelFinishLessonBtn">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</section>


@include('partials.learnerProfile')
</section>
@include('partials.footer')