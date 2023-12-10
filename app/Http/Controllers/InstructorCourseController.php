<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\Learner;
use App\Models\Instructor;
use App\Models\Admin;
use App\Models\Course;
use App\Models\LearnerCourse;
use App\Models\LessonContents;
use App\Models\ActivityContents;
use App\Models\ActivityContentCriterias;
use App\Models\Syllabus;
use App\Models\Lessons;
use App\Models\Activities;
use App\Models\Quizzes;
use App\Models\LearnerCourseProgress;
use App\Models\LearnerSyllabusProgress;
use App\Models\LearnerLessonProgress;
use App\Models\LearnerActivityProgress;
use App\Models\LearnerQuizProgress;
use App\Models\LearnerActivityOutput;
use App\Models\LearnerActivityCriteriaScore;
use App\Models\QuizContents;
use App\Models\QuizReferences;
use App\Models\Questions;
use App\Models\QuestionAnswers;
use App\Models\LearnerQuizOutputs;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View as FacadesView;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\URL;
use Dompdf\Dompdf;

class InstructorCourseController extends Controller
{
    public function courses(){
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            

            try {
                $query = DB::table('course')
                    ->select(
                        "course.course_id",
                        "course.course_name",
                        "course.course_code",
                        "instructor.instructor_lname",
                        "instructor.instructor_fname",
                        "instructor.profile_picture"
                    )
                ->where('course.instructor_id', '=', $instructor['instructor_id'])
                ->join('instructor', 'instructor.instructor_id', '=', 'course.instructor_id')
                ->orderBy("course.created_at", "ASC");

                $courses = $query->paginate(50);
                    // $courses = $query;
            } catch (\Exception $e) {
                dd($e->getMessage());
            }

        } else {
            return redirect('/instructor');
        }

        return view('instructor_course.courses' , compact('instructor', 'courses'))->with('title', 'My Courses');
    }

    
    public function courseCreate(){
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            // dd($instructor);

            // $color = '#' . Str::random(6);
            // dd($color);

        } else {
            return redirect('/instructor');
        }
        return view('instructor_course.coursesCreate', compact('instructor'))
        ->with(['title' => 'Creeate Course',
                'scripts' => ['instructor_course_create.js']]);
        
    }

    public function courseCreate_process(Request $request) {
        $instructor = session('instructor');

        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $courseData = $request->validate([
                    'course_name' => ['required'],
                    'course_description' => ['required'],
                    'course_difficulty' => ['required'],
                ]);

                $courseData['course_code'] = Str::random(6);
                $courseData['instructor_id'] = $instructor['instructor_id'];
    
                
                $course = Course::create($courseData);
                
                $folderName = $course->course_id . ' ' . $courseData['course_name'];
                $folderName = Str::slug($folderName, '_');
                $folderPath = 'public/courses/' . $folderName;
    
                if(!Storage::exists($folderPath)) {
                    Storage::makeDirectory($folderPath);
                }

       
                // $latestCourse = DB::table('course')->orderBy('created_at', 'DESC')->first();
                // $latestCourseID = $latestCourse->course_id;
    
                session()->flash('message', 'Course created Successfully');

                $response = [
                    'message' => 'Course created successfully',
                    'redirect_url' => '/instructor/courses',
                    'course_id' => $course->course_id,
                ];
        
                return response()->json($response);


                // return response()->json(['message' => 'Course created successfully', 'redirect_url' => '/instructor/courses']);
            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        }
    }


    
    public function overview(Course $course){
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            // dd($instructor);

            try {
                $course = DB::table('course')
                ->where('course_id', $course->course_id)
                ->first();

            } catch (\Exception $e) {
                dd($e->getMessage());
            }

        } else {
            return redirect('/instructor');
        }

        return view('instructor_course.courseOverview', compact('course'))
        ->with([
            'title' => 'Course Overview',
            'scripts' => ['instructor_course_manage.js'],
            'instructor' => $instructor,
        ]
        );
    }

    public function manage_course(Request $request, Course $course) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            // dd($instructor);

            try {

                $search_by = $request->input('searchBy');
                $search_val = $request->input('searchVal');

                $filter_date = $request->input('filterDate');
                $filter_status = $request->input('filterStatus');

                $course = DB::table('course')
                ->select(
                    "course.course_id",
                    "course.course_name",
                    "course.course_code",
                    "course.course_status",
                    "course.course_difficulty",
                    "course.course_description",
                    "course.created_at",
                    "course.updated_at",
                    "instructor.instructor_lname",
                    "instructor.instructor_fname",
                )
            ->where('course.course_id', $course->course_id)
            ->join('instructor', 'instructor.instructor_id', '=', 'course.instructor_id')
            ->first();


            $enrolleesQuery = DB::table('learner_course')
            ->select(
                'learner_course.learner_course_id',
                'learner_course.learner_id',
                'learner_course.status',
                'learner_course.created_at',
                'learner.learner_fname',
                'learner.learner_lname',
                'learner.learner_email'
            )
            ->join('learner', 'learner_course.learner_id', '=', 'learner.learner_id')
            ->orderBy('learner_course.created_at','DESC')
            ->where('learner_course.course_id', '=', $course->course_id);

            if(!empty($filter_date) || !empty($filter_status)) {
                if(!empty($filter_date) && empty($filter_date)) {
                    $enrolleesQuery->where('learner_course.created_at', 'LIKE', $filter_date.'%');
                } elseif (empty($filter_date) && !empty($filter_status)) {
                    $enrolleesQuery->where('learner_course.status', 'LIKE', $filter_status.'%');
                } else {
                    $enrolleesQuery->where('learner_course.created_at', 'LIKE', $filter_date.'%')
                        ->where('learner_course.status', 'LIKE', $filter_status.'%');
                }
            }

            if(!empty($search_by) && !empty($search_val)) {
                if($search_by == 'name') {
                    $enrolleesQuery->where(function ($enrolleesQuery) use ($search_val) {
                        $enrolleesQuery->where('learner.learner_fname', 'LIKE', $search_val.'%')
                            ->orWhere('learner.learner_lname', 'LIKE', $search_val.'%');
                    });
                } else if ($search_by == 'learner_course_id') {
                    $enrolleesQuery->where('learner_course.'.$search_by, 'LIKE', $search_val.'%');
                } else {
                    $enrolleesQuery->where('learner.'.$search_by, 'LIKE', $search_val. '%');
                }
            }

            $enrollees = $enrolleesQuery->get();


            } catch (\Exception $e) {
                dd($e->getMessage());
            }

        } else {
            return redirect('/instructor');
        }

        $response = [
            'course' => $course,
            'enrollees'=> $enrollees,
            'filterDate' => $filter_date,
            'filterStatus' => $filter_status,
            'searchBy' => $search_by,
            'searchVal' => $search_val,
        ];

        return response()->json($response);
    }



    public function update_course(Course $course, Request $request) {
        $instructor = session('instructor');

        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $courseData = $request->validate([
                    'course_name' => ['required'],
                    'course_description' => ['required'],
                    'course_difficulty' => ['required'],
                ]);

                $course->update($courseData);

                session()->flash('message', 'Course updated Successfully');
                return response()->json(['message' => 'Course updated successfully', 'redirect_url' => "/instructor/course/$course->course_id"]);
                
            
            } catch (ValidationException $e) {
                // dd($e->getMessage());
                $errors = $e->validator->errors();        
                return response()->json(['errors' => $errors], 422);
            }
        }
    }

    public function delete_course(Course $course) {
        $instructor = session('instructor');

        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $course->delete();


                session()->flash('message', 'Course deleted Successfully');
                return response()->json(['message' => 'Course deleted successfully', 'redirect_url' => "/instructor/courses"]);
                
            
            } catch (ValidationException $e) {
                // dd($e->getMessage());
                $errors = $e->validator->errors();        
                return response()->json(['errors' => $errors], 422);
            }
        }
    }

    public function create_syllabus(Request $request) {
        $syllabusData = $request->validate([
            'course_id' => ['required'],
            'topic_id' => ['required'],
            'topic_title'=> ['required'],
            'category'=> ['required'],
        ]);

        $syllabus = Syllabus::create($syllabusData);

        if($syllabusData['category'] == 'LESSON') {

            $lessonData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'lesson_title' => $syllabus->topic_title,
            ];

            $lesson = Lessons::create($lessonData);

        } else if($syllabusData['category'] == 'ACTIVITY') {
            $activityData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'activity_title' => $syllabus->topic_title,
            ];

            $activity = Activities::create($activityData);
        } else {
            $quizData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'quiz_title' => $syllabus->topic_title,
            ];

            $quiz = Quizzes::create($quizData);
        }

       
        // $latestSyllabus = DB::table('syllabus')->orderBy('created_at', 'DESC')->first();

        session()->flash('message', 'Syllabus created Successfully');

        $response = [
            'message' => 'Syllabus created successfully',
            'redirect_url' => '/instructor/courses',
            'syllabus' => $syllabus->syllabus_id,
        ];

        return response()->json($response);
    }


    public function display_course_syllabus_view(Course $course) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {

                    if (!function_exists('getRandomColor')) {
                        function getRandomColor() {
                        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Generate a random color for mainBackgroundCol
                    $mainBackgroundCol = getRandomColor();
        
                    // Darken the mainBackgroundCol
                    $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                    $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
        
                    // Darken the mainBackgroundCol further for darkenedColor
                    $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);
                    


                    $response = $this->course_content($course);

                    return view('instructor_course.courseContent', compact('instructor'))->with([
                        'title' => 'Course Content',
                        'scripts' => ['instructor_course_content_syllabus.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'mainBackgroundCol' => $mainBackgroundCol,
                        'darkenedColor' => $darkenedColor,
                        // 'instructor' => $response['instructor'],
                    ]);

                } catch (ValidationException $e) {
                    $errors = $e->validator->errors();
            
                    return response()->json(['errors' => $errors], 422);
                }
            }
        } else {
            return redirect('/instructor');
        }
    }
    public function course_content_json (Course $course) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $response = $this->course_content($course);


                $data = [    
                'title' => 'Course Content',
                'scripts' => ['instructor_course_content_syllabus.js'],
                'lessonCount' => $response['lessonCount'],
                'activityCount' => $response['activityCount'],
                'quizCount' => $response['quizCount'],
                'course' => $response['course'],
                'syllabus' => $response['syllabus'],
                ];

                return response()->json($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        }
    } else {
        return redirect('/instructor');
    }
    }
    public function course_content(Course $course) {
        $instructor = session('instructor');
        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $course = DB::table('course')
                ->select(
                    "course.course_id",
                    "course.course_name",
                    "course.course_code",
                    "course.course_description",
                    "course.course_status",
                    "course.course_difficulty",
                    "course.instructor_id",
                    "instructor.instructor_fname",
                    "instructor.instructor_lname",
                    "instructor.profile_picture",
                )
                ->join('instructor', 'instructor.instructor_id', '=', 'course.instructor_id')
                ->where('course_id', $course->course_id)
                ->first();

                $syllabus = DB::table('syllabus')
                ->select(
                    "syllabus_id",
                    "topic_id",
                    "topic_title",
                    "category"
                )
                ->where('course_id', $course->course_id)
                ->orderBy('topic_id', 'ASC')
                ->get();

                $lessonCount = 0;
                $quizCount = 0;
                $activityCount = 0;

                foreach($syllabus as $topic) {
                    if($topic->category == 'LESSON') {
                        $lessonCount++;
                    } else if($topic->category == 'ACTIVITY') {
                        $activityCount++;
                    } else {
                        $quizCount++;
                    }
                }


                $data = [
                    'course' => $course,
                    'syllabus' => $syllabus,
                    'lessonCount' => $lessonCount,
                    'quizCount' => $quizCount,
                    'activityCount' => $activityCount,
                    'instructor' => $instructor,
                ];

                return $data;

                // return view('instructor_course.courseContent', compact('instructor', 'course', 'syllabus'))->with([
                //     'title' => 'Course Content',
                //     'scripts' => ['instructor_course_content_syllabus.js'],
                //     'lessonCount' => $lessonCount,
                //     'activityCount' => $activityCount,
                //     'quizCount' => $quizCount,
                // ]);
            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        }
    }

    public function update_syllabus(Course $course, Request $request) {

        try {

            $syllabusData = $request->validate([
                'topic_id' => ['required'],
                'topic_title' => ['required'],
                'category' => ['required'],
            ]);

            Syllabus::where('syllabus_id', $request->input('syllabus_id'))
                        ->where('course_id', $course->course_id)
                        ->update($syllabusData);

            $syllabus = DB::table('syllabus')
                        ->select(
                            'syllabus_id',
                            'course_id',
                            'topic_id',
                            'topic_title',
                            'category'
                        )
                        ->where('syllabus_id', $request->input('syllabus_id'))
                        ->where('course_id', $course->course_id)
                        ->first();

             if($syllabusData['category'] == 'LESSON') {

            $lessonData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'lesson_title' => $syllabus->topic_title,
            ];

            // $lesson = Lessons::create($lessonData);

            $lesson = Lessons::where('syllabus_id', $request->input('syllabus_id'))
                        ->where('course_id', $course->course_id)
                        ->update($lessonData);

        } else if($syllabusData['category'] == 'ACTIVITY') {
            $activityData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'activity_title' => $syllabus->topic_title,
            ];

            // $activity = Activities::create($activityData);

            $activity = Activities::where('syllabus_id', $request->input('syllabus_id'))
                        ->where('course_id', $course->course_id)
                        ->update($activityData);
        } else {
            $quizData = [
                'syllabus_id'=> $syllabus->syllabus_id,
                'course_id' => $syllabus->course_id,
                'topic_id' => $syllabus->topic_id,
                'quiz_title' => $syllabus->topic_title,
            ];

            // $quiz = Quizzes::create($quizData);

            $quiz = Quizzes::where('syllabus_id', $request->input('syllabus_id'))
                        ->where('course_id', $course->course_id)
                        ->update($quizData);
        }

            session()->flash('message', 'Syllabus updated Successfully');
            return response()->json(['message' => 'Course updated successfully', 'redirect_url' => "/instructor/course/content/$course->course_id"]);
                        
                    
            } catch (ValidationException $e) {
                // dd($e->getMessage());
                $errors = $e->validator->errors();        
                return response()->json(['errors' => $errors], 422);
            }
    }

    public function update_syllabus_add_new(Course $course, Request $request) {
        try {
            $syllabusData = $request->validate([
                'topic_id' => ['required'],
                'topic_title' => ['required'],
                'category' => ['required'],
            ]);
    
            $syllabus = Syllabus::create([
                'topic_id' => $syllabusData['topic_id'],
                'topic_title' => $syllabusData['topic_title'],
                'category' => $syllabusData['category'],
                'course_id' => $course->course_id,
            ]);
    
            if ($syllabusData['category'] == 'LESSON') {
                Lessons::create([
                    'syllabus_id' => $syllabus->syllabus_id,
                    'course_id' => $syllabus->course_id,
                    'topic_id' => $syllabus->topic_id,
                    'lesson_title' => $syllabus->topic_title,
                ]);
            } elseif ($syllabusData['category'] == 'ACTIVITY') {
                Activities::create([
                    'syllabus_id' => $syllabus->syllabus_id,
                    'course_id' => $syllabus->course_id,
                    'topic_id' => $syllabus->topic_id,
                    'activity_title' => $syllabus->topic_title,
                ]);
            } else {
                Quizzes::create([
                    'syllabus_id' => $syllabus->syllabus_id,
                    'course_id' => $syllabus->course_id,
                    'topic_id' => $syllabus->topic_id,
                    'quiz_title' => $syllabus->topic_title,
                ]);
            }
    
            session()->flash('message', 'Syllabus updated Successfully');
            return response()->json(['message' => 'Course updated successfully', 'redirect_url' => "/instructor/course/content/$course->course_id"]);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function update_syllabus_delete(Course $course, Request $request) {
        try {
            $syllabusId = $request->input('fetch_syllabus_id');
            $syllabus = DB::table('syllabus')
                ->select(
                    'syllabus_id',
                    'course_id',
                    'topic_id',
                    'topic_title',
                    'category'
                )
                ->where('syllabus_id', $syllabusId)
                // ->where('course_id', $course->course_id)
                ->first();
    
            if ($syllabus) {
                if ($syllabus->category == 'LESSON') {
                    DB::table('lessons')
                        ->where('syllabus_id', $syllabusId)
                        ->where('course_id', $course->course_id)
                        ->delete();
                } elseif ($syllabus->category == 'ACTIVITY') {
                    DB::table('activities')
                        ->where('syllabus_id', $syllabusId)
                        ->where('course_id', $course->course_id)
                        ->delete();
                } elseif ($syllabus->category == 'QUIZ') {
                    DB::table('quizzes')
                        ->where('syllabus_id', $syllabusId)
                        ->where('course_id', $course->course_id)
                        ->delete();
                }
    
                DB::table('syllabus')
                    ->where('syllabus_id', $syllabusId)
                    ->where('course_id', $course->course_id)
                    ->delete();
    
                session()->flash('message', 'Topic deleted Successfully');
                return response()->json(['message' => 'Topic Deleted successfully', 'redirect_url' => "/instructor/course/content/$course->course_id"]);
            } else {
                return response()->json(['error' => 'Syllabus not found'], 404);
            }
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            return response()->json(['errors' => $errors], 422);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    public function view_lesson(Course $course, Syllabus $syllabus, $topic_id) {


        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {

                    $lessonInfo = DB::table('lessons')
                        ->select(
                            'lesson_id',
                            'course_id',
                            'syllabus_id',
                            'topic_id',
                            'lesson_title',
                            'picture',
                        )
                        ->where('course_id', $course->course_id)
                        ->where('syllabus_id', $syllabus->syllabus_id)
                        ->where('topic_id', $topic_id)
                        ->first();
                            // dd($lessonInfo);

                            if ($lessonInfo === null) {
                                // Set $activityContent to null or an empty array if it's appropriate
                                $lessonContent = null; // or $activityContent = [];
                            
                                // You can also provide a message to indicate that no data was found
                                session()->flash('message', 'Please Save the Syllabus First');
                                return redirect("/instructor/course/content/$course->course_id");

                            } else {
                                // Fetch $activityContent as you normally would
                                $lessonContent = DB::table('lesson_content')
                            ->select(
                                'lesson_content_id',
                                'lesson_id',
                                'lesson_content_title',
                                'lesson_content',
                                'lesson_content_order',
                                'picture',
                                'video_url'
                            )
                            ->where('lesson_id', $lessonInfo->lesson_id)
                            ->orderBy('lesson_content_order', 'ASC')
                            ->get();
                            }


                    

                                // dd($lessonContent);

                    $response = $this->course_content($course);

                    session(['lesson_data' => [
                        'lessonInfo' => $lessonInfo,
                        'lessonContent' => $lessonContent,
                        'courseData' => $response,
                        'instructor' => $instructor,
                        'title' => 'Course Lesson',
                    ]]);

                    return view('instructor_course.courseLesson', compact('instructor'))->with([
                        'title' => 'Course Lesson',
                        'scripts' => ['instructor_lesson_manage.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'lessonInfo' => $lessonInfo,
                        'lessonContent' => $lessonContent,
                        // 'instructor' => $response['instructor'],
                    ]);

                } catch (ValidationException $e) {
                    $errors = $e->validator->errors();
            
                    return response()->json(['errors' => $errors], 422);
                }
            }
        } else {
            return redirect('/instructor');
        }

        return view('instructor_course.courseLesson')->with('title', 'Course Lesson');
    }

    public function lesson_content_json (Course $course, Syllabus $syllabus, $topic_id) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
        if($instructor['status'] !== 'Approved') {
            session()->flash('message', 'Account is not yet Approved');
            return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
        } else {
            try {
                $lessonInfo = DB::table('lessons')
                        ->select(
                            'lesson_id',
                            'course_id',
                            'syllabus_id',
                            'topic_id',
                            'lesson_title',
                            'picture',
                        )
                        ->where('course_id', $course->course_id)
                        ->where('syllabus_id', $syllabus->syllabus_id)
                        ->where('topic_id', $topic_id)
                        ->first();

                $lessonContent = DB::table('lesson_content')
                        ->select(
                            'lesson_content_id',
                            'lesson_id',
                            'lesson_content_title',
                            'lesson_content',
                            'lesson_content_order',
                            'picture',
                            'video_url',
                        )
                        ->where('lesson_id', $lessonInfo->lesson_id)
                        ->orderBy('lesson_content_order', 'ASC')
                        ->get();


                $response = $this->course_content($course);


                $data = [    
                'title' => 'Course Content',
                'scripts' => ['instructor_course_content_syllabus.js'],
                'lessonCount' => $response['lessonCount'],
                'activityCount' => $response['activityCount'],
                'quizCount' => $response['quizCount'],
                'course' => $response['course'],
                'syllabus' => $response['syllabus'],
                'lessonInfo' => $lessonInfo,
                'lessonContent' => $lessonContent,
                ];

                return response()->json($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        }
    } else {
        return redirect('/instructor');
    }
    }

    public function update_lesson_title(Course $course, Syllabus $syllabus, Request $request, $topic_id, $lesson_id) {
        try {

            // Validate the request...
            $updated_values = $request->validate([
                'lesson_title' => ['required'],
            ]);
            $updated_values2 = $request->validate([
                'topic_title' => ['required'],
            ]);


            DB::table('lessons')
                ->where('lesson_id', $lesson_id)
                ->update($updated_values);

            DB::table('syllabus')
                ->where('syllabus_id', $syllabus->syllabus_id)
                ->where('topic_id', $topic_id)
                ->update($updated_values2);

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function update_lesson_picture(Course $course, Syllabus $syllabus, Request $request, $topic_id, Lessons $lesson) {
        try {

            $lessonData = DB::table('lessons')
            ->select(
                'picture'
            )
            ->where('lesson_id' , $lesson->lesson_id)
            ->first();

            if($lessonData->picture !== null) {
                $relativeFilePath = str_replace('public/', '', $lesson->picture);
                
                if (Storage::disk('public')->exists($relativeFilePath)) {
                    // Storage::disk('public')->delete($relativeFilePath);
                    $specifiedDir = explode('/', $relativeFilePath);
                    array_pop($specifiedDir);

                    $dirPath = implode('/', $specifiedDir);

                    // dd($dirPath);
                    if (Storage::disk('public')->exists($relativeFilePath)) {
                        Storage::disk('public')->delete($relativeFilePath);
                    }
                }
            }
                

            $pictureData = $request->validate([
                'picture' => 'required|image|mimes:jpeg,png,jpg,gif',
            ]);

            $folderName = "{$course->course_id} {$course->course_name}";
            $folderName = Str::slug($folderName, '_');
            $fileName = time() . ' - '. $course->course_name . ' - ' . $pictureData['picture']->getClientOriginalName();
            $folderPath = "courses/" .$folderName;

            $filePath = $pictureData['picture']->storeAs($folderPath, $fileName, 'public');

            Lessons::where('lesson_id' , $lesson->lesson_id)
            ->update(['picture' => $filePath]);

            if(!Storage::exists($folderPath)) { 
            Storage::makeDirectory($folderPath);
        }


        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function update_lesson_content(Lessons $lesson, LessonContents $lesson_content, Request $request) {
        try {
            $updated_values = $request->validate([
                'lesson_content_title' => ['nullable'],
                'lesson_content' => ['nullable'],
            ]);

            DB::table('lesson_content')
                ->where('lesson_id', $lesson->lesson_id)
                ->where('lesson_content_id', $lesson_content->lesson_content_id)
                ->update($updated_values);

            return response()->json();

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    } 

    public function delete_lesson_content (Lessons $lesson, LessonContents $lesson_content) {
        try {
            DB::table('lesson_content')
                ->where('lesson_id', $lesson->lesson_id)
                ->where('lesson_content_id', $lesson_content->lesson_content_id)
                ->delete();

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function save_lesson_content(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, Request $request) {
        try {

            $lessonContentData = $request->validate([
                'lesson_content_id' => ['required'],
                'lesson_content_title' => ['required'],
                'lesson_content' => ['required'],
                'lesson_content_order' => ['required']
            ]);

            LessonContents::where('lesson_id', $lesson->lesson_id)
                        ->where('lesson_content_id', $lessonContentData['lesson_content_id'])
                        ->update($lessonContentData);


            session()->flash('message', 'Lesson Content updated Successfully');
            return response()->json(['message' => 'Lesson Content updated successfully', 'redirect_url' => "/instructor/course/content/$course->course_id/$syllabus->syllabus_id/lesson/$topic_id"]);
                        
                    
            } catch (ValidationException $e) {
                // dd($e->getMessage());
                $errors = $e->validator->errors();        
                return response()->json(['errors' => $errors], 422);
            }
    }

    public function save_add_lesson_content(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, Request $request) {

        try{
        $lessonContentData = $request->validate([
            'lesson_content_title' => ['nullable'],
            'lesson_content' => ['nullable'],
            'lesson_content_order' => ['required']
        ]);

        $lessonContent = LessonContents::create([
            'lesson_content_title' => $lessonContentData['lesson_content_title'],
            'lesson_content' => $lessonContentData['lesson_content'],
            'lesson_id' => $lesson->lesson_id,
            'lesson_content_order' => $lessonContentData['lesson_content_order']
        ]);


        session()->flash('message', 'Lesson Content updated Successfully');
        return response()->json(['message' => 'Lesson Content updated successfully', 'redirect_url' => "/instructor/course/content/$course->course_id/$syllabus->syllabus_id/lesson/$topic_id"]);
                    
                
        } catch (ValidationException $e) {
            // dd($e->getMessage());
            $errors = $e->validator->errors();        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function lesson_content_store_file(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, LessonContents $lesson_content, Request $request) {
        try {


            $lessonContentData = DB::table('lesson_content')
            ->select(
                'picture'
            )
            ->where('lesson_content_id' , $lesson_content->lesson_content_id)
            ->first();

            if($lessonContentData->picture !== null) {
                $relativeFilePath = str_replace('public/', '', $lesson_content->picture);
                
                if (Storage::disk('public')->exists($relativeFilePath)) {
                    // Storage::disk('public')->delete($relativeFilePath);
                    $specifiedDir = explode('/', $relativeFilePath);
                    array_pop($specifiedDir);

                    $dirPath = implode('/', $specifiedDir);

                    // dd($dirPath);
                    if (Storage::disk('public')->exists($relativeFilePath)) {
                        Storage::disk('public')->delete($relativeFilePath);
                    }
                }

            }

            $pictureData = $request->validate([
                'picture' => 'required|image|mimes:jpeg,png,jpg,gif',
            ]);

            $folderName = "{$course->course_id} {$course->course_name}";
            $folderName = Str::slug($folderName, '_');
            $fileName = time() . ' - '. $course->course_name . ' - ' . $pictureData['picture']->getClientOriginalName();
            $folderPath = "courses/" .$folderName;

            $filePath = $pictureData['picture']->storeAs($folderPath, $fileName, 'public');

            LessonContents::where('lesson_content_id' , $lesson_content['lesson_content_id'])
            ->update(['picture' => $filePath]);

            if(!Storage::exists($folderPath)) { 
            Storage::makeDirectory($folderPath);
        }


        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function lesson_content_delete_file(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, LessonContents $lesson_content) {
        try {

            $relativeFilePath = str_replace('public/', '', $lesson_content->picture);
            if (Storage::disk('public')->exists($relativeFilePath)) {
                // Storage::disk('public')->delete($relativeFilePath);
                $specifiedDir = explode('/', $relativeFilePath);
                array_pop($specifiedDir);

                $dirPath = implode('/', $specifiedDir);

                // dd($dirPath);
                if (Storage::disk('public')->exists($relativeFilePath)) {
                    Storage::disk('public')->delete($relativeFilePath);
                }
            }

            $updatedRow = [
                'picture' => null
            ];
                
            DB::table('lesson_content')
                ->where('lesson_id', $lesson->lesson_id)
                ->where('lesson_content_id', $lesson_content->lesson_content_id)
                ->update($updatedRow);

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function lesson_content_embed_url(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, LessonContents $lesson_content, Request $request) {
        try {

            $embedUrlData = $request->input('video_url');

            LessonContents::where('lesson_content_id' , $lesson_content['lesson_content_id'])
            ->update(['video_url' => $embedUrlData]);


        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function lesson_content_delete_url (Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson, LessonContents $lesson_content) {
        try {
            $updatedRow = [
                'video_url' => null
            ];
                
            DB::table('lesson_content')
                ->where('lesson_id', $lesson->lesson_id)
                ->where('lesson_content_id', $lesson_content->lesson_content_id)
                ->update($updatedRow);

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function lesson_generate_pdf(Course $course, Syllabus $syllabus, $topic_id, Lessons $lesson)
{
    if (auth('instructor')->check()) {
        $instructor = session('instructor');
        
        // Retrieve the data from the session
        $lessonData = session('lesson_data');
    
        if (!$lessonData) {
            // Handle the case where the session data is not found
            return response('Session data not found', 500);
        }
    
        // Extract the data you need from the session
        $lessonInfo = $lessonData['lessonInfo'];
        $lessonContent = $lessonData['lessonContent'];
        $title = 'Course Lesson';
        $scripts = ['instructor_lesson_manage.js'];
        $courseData = $lessonData['courseData'];

        $course = $courseData['course'];
        $syllabus = $courseData['syllabus'];
        $lessonCount = $courseData['lessonCount'];
        $activityCount = $courseData['activityCount'];
        $quizCount = $courseData['quizCount'];

        // You can now use $lessonInfo and $lessonContent to generate your PDF
        // ...

        // Render the view with the Blade template
        $html = view('instructor_course.courseLesson', compact('instructor'))
            ->with([
                'title' => $title,
                'scripts' => $scripts,
                'lessonCount' => $lessonCount,
                'activityCount' => $activityCount,
                'quizCount' => $quizCount,
                'course' => $course,
                'syllabus' => $syllabus,
                'lessonInfo' => $lessonInfo,
                'lessonContent' => $lessonContent,
            ])
            ->render();

        // Find the markers in your HTML
        $startMarker = '<!-- start-generate-pdf -->';
        $endMarker = '<!-- end-generate-pdf -->';
    
        // Find the positions of the markers
        $startPos = strpos($html, $startMarker);
        $endPos = strpos($html, $endMarker);

        $extractedHtml = substr($html, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));

        // Generate a unique filename for the PDF (you can customize this)
        $filename = 'lesson_' . $lessonInfo->lesson_id . '.pdf';

        // Define the folder path based on the course name
        $folderName = Str::slug($course->course_name, '_'); // Converts course name to a URL-friendly format
        $folderPath = 'courses/' . $folderName;

        // Check if the file already exists
        if (Storage::disk('public')->exists($folderPath . '/' . $filename)) {
            // If it exists, delete the old file
            Storage::disk('public')->delete($folderPath . '/' . $filename);
        }

        // Configure Dompdf
        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isPhpEnabled', true);
        $dompdf->set_option('isCssEnabled', true);

        // Load HTML to Dompdf
        $dompdf->loadHtml($extractedHtml);

        // (Optional) Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the PDF
        $dompdf->render();

        // Generate the PDF
        $pdf = $dompdf->output();

        // Store the new PDF in the public directory within the course-specific folder
        Storage::disk('public')->put($folderPath . '/' . $filename, $pdf);

        // Generate the URL to the stored PDF
        $pdfUrl = URL::to('storage/' . $folderPath . '/' . $filename);

        // Provide a download link to the user
        return response()->json(['pdf_url' => $pdfUrl]);
    } else {
        // Handle authentication failure
        return response('Unauthorized', 401);
    }
}


    
    // for course activity
    public function view_activity(Course $course, Syllabus $syllabus, $topic_id) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {

                    if (!function_exists('getRandomColor')) {
                        function getRandomColor() {
                        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Generate a random color for mainBackgroundCol
                    $mainBackgroundCol = getRandomColor();
        
                    // Darken the mainBackgroundCol
                    $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                    $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
        
                    // Darken the mainBackgroundCol further for darkenedColor
                    $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);
    
    

                    $activityInfo = DB::table('activities')
    ->select(
        'activity_id',
        'course_id',
        'syllabus_id',
        'topic_id',
        'activity_title',
    )
    ->where('course_id', $course->course_id)
    ->where('syllabus_id', $syllabus->syllabus_id)
    ->where('topic_id', $topic_id)
    ->first();

if ($activityInfo === null) {
    // Set $activityContent to null or an empty array if it's appropriate
    $activityContent = null; // or $activityContent = [];
    $activityContentCriteria = null;

    // You can also provide a message to indicate that no data was found
    session()->flash('message', 'Please Save the Syllabus First');
    return redirect("/instructor/course/content/$course->course_id");
} else {
    // Fetch $activityContent as you normally would
    $activityContent = DB::table('activity_content')
        ->select(
            'activity_content_id',
            'activity_id',
            'activity_instructions',
            'total_score',
        )
        ->where('activity_id', $activityInfo->activity_id)
        ->get();

    // Check if $activityContent is empty, and if so, create a new row
    if ($activityContent->isEmpty()) {
        $newActivityContent = [
            'activity_id' => $activityInfo->activity_id,
            'activity_instructions' => 'Default Instructions', // You can set default values here
            'total_score' => 0, // You can set default values here
        ];
        DB::table('activity_content')->insert($newActivityContent);

        // Fetch the newly inserted row
        $activityContent = DB::table('activity_content')
            ->where('activity_id', $activityInfo->activity_id)
            ->get();
    }

    // Check if $activityContentCriteria is empty, and if so, create a new row
    if ($activityContent->isNotEmpty()) {
        $activityContentCriteria = DB::table('activity_content_criteria')
            ->select(
                'activity_content_criteria_id',
                'activity_content_id',
                'criteria_title',
                'score'
            )
            ->whereIn('activity_content_id', $activityContent->pluck('activity_content_id')->toArray())
            ->get();

        if ($activityContentCriteria->isEmpty()) {
            $newActivityContentCriteria = [
                'activity_content_id' => $activityContent[0]->activity_content_id,
                'criteria_title' => 'Default Criteria', // You can set default values here
                'score' => 0, // You can set default values here
            ];
            DB::table('activity_content_criteria')->insert($newActivityContentCriteria);

            // Fetch the newly inserted row
            $activityContentCriteria = DB::table('activity_content_criteria')
                ->where('activity_content_id', $activityContent[0]->activity_content_id)
                ->get();
        }
    }
}

                                // dd($lessonContent);

                    $response = $this->course_content($course);

                    session(['activity_data' => [
                        'activityInfo' => $activityInfo,
                        'activityContent' => $activityContent,
                        'activityContentCriteria' => $activityContentCriteria,
                        'courseData' => $response,
                        'instructor' => $instructor,
                        'title' => 'Course Lesson',
                    ]]);

                    return view('instructor_course.courseActivity', compact('instructor'))->with([
                        'title' => 'Course Lesson',
                        'mainBackgroundCol' => $mainBackgroundCol,
                        'scripts' => ['instructorActivities.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'activityInfo' => $activityInfo,
                        'activityContent' => $activityContent,
                        'activityContentCriteria' => $activityContentCriteria,
                        // 'instructor' => $response['instructor'],
                    ]);

                } catch (ValidationException $e) {
                    $errors = $e->validator->errors();
            
                    return response()->json(['errors' => $errors], 422);
                }
            }
        } else {
            return redirect('/instructor');
        }

    
    }

    public function activity_content_json(Course $course, Syllabus $syllabus, $topic_id) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {

                    $activityInfo = DB::table('activities')
                        ->select(
                            'activity_id',
                            'course_id',
                            'syllabus_id',
                            'topic_id',
                            'activity_title',
                        )
                        ->where('course_id', $course->course_id)
                        ->where('syllabus_id', $syllabus->syllabus_id)
                        ->where('topic_id', $topic_id)
                        ->first();

                            if ($activityInfo === null) {
                                // Set $activityContent to null or an empty array if it's appropriate
                                $activityContent = null; // or $activityContent = [];
                                $activityContentCriteria = null;
                            
                                // You can also provide a message to indicate that no data was found
                                session()->flash('message', 'Please Save the Syllabus First');
                                return redirect("/instructor/course/content/$course->course_id");

                            } else {
                                // Fetch $activityContent as you normally would
                                $activityContent = DB::table('activity_content')
                                    ->select(
                                        'activity_content_id',
                                        'activity_id',
                                        'activity_instructions',
                                        'total_score',
                                    )
                                    ->where('activity_id', $activityInfo->activity_id)
                                    ->get();

                                    if($activityContent === null) {
                                        $activityContentCriteria = null;
                                    } else {
                                        $activityContentCriteria = DB::table('activity_content_criteria')
                                        ->select(
                                            'activity_content_criteria_id',
                                            'activity_content_id',
                                            'criteria_title',
                                            'score'
                                        )
                                        ->whereIn('activity_content_id', $activityContent->pluck('activity_content_id')->toArray()) // Use pluck to get an array of activity_content_id values
                                        ->get();
                                    }
                               
                            }

                                // dd($lessonContent);

                    $response = $this->course_content($course);

                    session(['activity_data' => [
                        'activityInfo' => $activityInfo,
                        'activityContent' => $activityContent,
                        'activityContentCriteria' => $activityContentCriteria,
                        'courseData' => $response,
                        'instructor' => $instructor,
                        'title' => 'Course Lesson',
                    ]]);

                    
                    $learnerActivityOutput = DB::table('learner_activity_progress')
                    ->select(
                        'learner_activity_progress.learner_activity_progress_id',
                        'learner_activity_progress.learner_course_id',
                        'learner_activity_progress.learner_id',
                        'learner_activity_progress.course_id',
                        'learner_activity_progress.syllabus_id',
                        'learner_activity_progress.activity_id',
                        'learner_activity_progress.status',
                        'learner_activity_progress.created_at',

                        'learner.learner_fname',
                        'learner.learner_lname',

                        'learner_activity_output.total_score'
                    )
                    ->join('learner', 'learner.learner_id', '=', 'learner_activity_progress.learner_id')
                    ->join('learner_activity_output', function ($join) {
                        $join->on('learner_activity_output.learner_course_id', '=', 'learner_activity_progress.learner_course_id')
                            ->on('learner_activity_output.syllabus_id', '=', 'learner_activity_progress.syllabus_id')
                            ->on('learner_activity_output.course_id', '=', 'learner_activity_progress.course_id')
                            ->on('learner_activity_output.activity_id', '=', 'learner_activity_progress.activity_id');
                    })
                    ->where('learner_activity_progress.course_id', $course->course_id)
                    ->where('learner_activity_progress.syllabus_id', $syllabus->syllabus_id)
                    ->where('learner_activity_progress.activity_id', $activityInfo->activity_id)
                    ->get();

                      $data = [    
                        'title' => 'Course Lesson',
                        'scripts' => ['instructorActivities.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'activityInfo' => $activityInfo,
                        'activityContent' => $activityContent,
                        'activityContentCriteria' => $activityContentCriteria,
                        'learnerActivityContent' => $learnerActivityOutput,
                        ];


                    return response()->json($data);

                } catch (ValidationException $e) {
                    $errors = $e->validator->errors();
            
                    return response()->json(['errors' => $errors], 422);
                }
            }
        } else {
            return redirect('/instructor');
        }

    
    }

    public function update_activity_instructions(Course $course, Syllabus $syllabus, $topic_id, Activities $activity, ActivityContents $activity_content, Request $request) {
        try {
            $updated_values = $request->validate([
                'activity_instructions' => ['required'],
            ]);

            DB::table('activity_content')
                ->where('activity_id', $activity->activity_id)
                ->where('activity_content_id', $activity_content->activity_content_id)
                ->update($updated_values);

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function update_activity_score(Course $course, Syllabus $syllabus, $topic_id, Activities $activity, ActivityContents $activity_content, Request $request) {
        try {
            $updated_values = $request->validate([
                'total_score' => ['required'],
            ]);

            DB::table('activity_content')
                ->where('activity_id', $activity->activity_id)
                ->where('activity_content_id', $activity_content->activity_content_id)
                ->update($updated_values);

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function update_activity_criteria(Course $course, Syllabus $syllabus, $topic_id, Activities $activity, ActivityContents $activity_content, Request $request) {
        try {
            $updated_criterias = $request->validate([
                'activity_content_id' => ['required'],
                'criteria_title' => ['required'],
                'score' => ['required'],
            ]);

            ActivityContentCriterias::where('activity_content_id', $activity_content->activity_content_id)
            ->delete();

            $activityContentCriteria = ActivityContentCriterias::create($updated_criterias);


        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function add_activity_criteria(Course $course, Syllabus $syllabus, $topic_id, Activities $activity, ActivityContents $activity_content, Request $request) {
        try {
            $updated_criterias = $request->validate([
                'activity_content_id' => ['required'],
                'criteria_title' => ['required'],
                'score' => ['required'],
            ]);

            $activityContentCriteria = ActivityContentCriterias::create($updated_criterias);


        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function view_learner_activity_response(Course $course, Syllabus $syllabus, $topic_id, LearnerCourse $learner_course) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');

            try {

                if (!function_exists('getRandomColor')) {
                        function getRandomColor() {
                        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Generate a random color for mainBackgroundCol
                    $mainBackgroundCol = getRandomColor();
        
                    // Darken the mainBackgroundCol
                    $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                    $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
        
                    // Darken the mainBackgroundCol further for darkenedColor
                    $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);
    
    

                $activityData = DB::table('activities')
                ->select(
                    'activities.activity_id',
                    'activities.course_id',
                    'activities.syllabus_id',
                    'activities.topic_id',
                    'activities.activity_title',

                    'activity_content.activity_content_id',
                    'activity_content.activity_instructions',
                    'activity_content.total_score'
                )
                ->join('activity_content', 'activity_content.activity_id', '=', 'activities.activity_id')
                ->where('activities.course_id', $course->course_id)
                ->where('activities.syllabus_id', $syllabus->syllabus_id)
                ->where('activities.topic_id', $topic_id)
                ->first();


                $learnerActivityData = DB::table('learner_activity_output')
                ->select(
                    'learner_activity_output.learner_activity_output_id',
                    'learner_activity_output.learner_course_id',
                    'learner_activity_output.syllabus_id',
                    'learner_activity_output.activity_id',
                    'learner_activity_output.activity_content_id',
                    'learner_activity_output.course_id',
                    'learner_activity_output.answer',
                    'learner_activity_output.total_score',
                    'learner_activity_output.remarks',
                    'learner_activity_output.created_at',

                    'learner_course.learner_id',

                    'learner.learner_fname',
                    'learner.learner_lname'
                )
                ->join('learner_course', 'learner_course.learner_course_id', '=', 'learner_activity_output.learner_course_id')
                ->join('learner', 'learner.learner_id', '=', 'learner_course.learner_id')
                ->where('learner_activity_output.learner_course_id', $learner_course->learner_course_id)
                ->where('learner_activity_output.course_id', $course->course_id)
                ->where('learner_activity_output.syllabus_id', $syllabus->syllabus_id)
                ->where('learner_activity_output.activity_id', $activityData->activity_id)
                ->where('learner_activity_output.activity_content_id', $activityData->activity_content_id)
                ->first();

                $learnerActivityScoreData = DB::table('learner_activity_criteria_score')
                ->select(
                    'learner_activity_criteria_score.learner_activity_criteria_score_id',
                    'learner_activity_criteria_score.learner_activity_output_id',
                    'learner_activity_criteria_score.activity_content_criteria_id',
                    'learner_activity_criteria_score.activity_content_id',
                    'learner_activity_criteria_score.score',

                    'activity_content_criteria.criteria_title',
                    'activity_content_criteria.score as criteria_score'
                )
                ->join('activity_content_criteria', 'activity_content_criteria.activity_content_criteria_id', '=', 'learner_activity_criteria_score.activity_content_criteria_id')
                ->where('learner_activity_criteria_score.learner_activity_output_id', $learnerActivityData->learner_activity_output_id)
                ->where('learner_activity_criteria_score.activity_content_id', $learnerActivityData->activity_content_id)
                ->orderBy('learner_activity_criteria_score.activity_content_criteria_id', 'ASC')
                ->get();

                $response = $this->course_content($course);

               $data = [
                'title' => 'Activity Output',
                'scripts' => ['instructorActivities_learnerResponse.js'],
                'mainBackgroundCol' => $mainBackgroundCol,
                'activity' => $activityData,
                'learnerActivityOutput' => $learnerActivityData,
                'learnerActivityScore' => $learnerActivityScoreData,
                'course' => $response['course'],
               ];
                // dd($data);

                return view('instructor_course.courseActivity_viewLearnerResponse', compact('instructor'))->with($data);

            } catch(\Exception $e) {
                dd($e->getMessage());
            }


        } else {
            return redirect('/instructor');
        }
            
    }

    public function learnerResponse_overallScore($learner_activity_output, $learner_course, $activity, $activity_content, Request $request) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
    
            try {
                $remarks = $request->input('remarks');
                $totalScore = $request->input('total_score');
    
                $learnerActivityOutputData = DB::table('learner_activity_output')
                    ->select(
                        'learner_activity_output_id',
                        'learner_course_id',
                        'activity_id',
                        'syllabus_id',
                        'activity_content_id',
                        'course_id'
                    )
                    ->where('learner_activity_output_id', $learner_activity_output)
                    ->where('learner_course_id', $learner_course)
                    ->where('activity_id', $activity)
                    ->where('activity_content_id', $activity_content)
                    ->first();
    
                // Update remarks and total score regardless of the current syllabus status
                DB::table('learner_activity_output')
                    ->where('learner_activity_output_id', $learner_activity_output)
                    ->where('learner_course_id', $learner_course)
                    ->where('activity_id', $activity)
                    ->where('activity_content_id', $activity_content)
                    ->update([
                        'remarks' => $remarks,
                        'total_score' => $totalScore,
                    ]);
    
                $currentSyllabusStatus = DB::table('learner_syllabus_progress')
                    ->where('learner_course_id', $learnerActivityOutputData->learner_course_id)
                    ->where('course_id', $learnerActivityOutputData->course_id)
                    ->where('syllabus_id', $learnerActivityOutputData->syllabus_id)
                    ->value('status');
    
                if ($currentSyllabusStatus !== 'COMPLETED') {
                    DB::table('learner_syllabus_progress')
                        ->where('learner_course_id', $learnerActivityOutputData->learner_course_id)
                        ->where('course_id', $learnerActivityOutputData->course_id)
                        ->where('syllabus_id', $learnerActivityOutputData->syllabus_id)
                        ->update([
                            'status' => "COMPLETED",
                        ]);
    
                    DB::table('learner_activity_progress')
                        ->where('learner_course_id', $learnerActivityOutputData->learner_course_id)
                        ->where('course_id', $learnerActivityOutputData->course_id)
                        ->where('syllabus_id', $learnerActivityOutputData->syllabus_id)
                        ->where('activity_id', $learnerActivityOutputData->activity_id)
                        ->update([
                            'status' => "COMPLETED",
                        ]);
    
                    $learnerSyllabusProgress = DB::table('learner_syllabus_progress')
                        ->select('learner_syllabus_progress_id', 'syllabus_id', 'category', 'status', /* add other columns as needed */)
                        ->where('learner_course_id', $learnerActivityOutputData->learner_course_id)
                        ->where('status', '=', 'LOCKED')
                        ->orderBy('learner_syllabus_progress_id', 'ASC')
                        ->first();
    
                    if ($learnerSyllabusProgress) {
                        DB::table('learner_syllabus_progress')
                            ->where('learner_syllabus_progress_id', $learnerSyllabusProgress->learner_syllabus_progress_id)
                            ->update(['status' => 'NOT YET STARTED']);
                    }
                }
    
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
    
        } else {
            return redirect('/instructor');
        }
    }
    
    
    

    public function learnerResponse_criteriaScore ($learner_activity_output, $learner_course,  $activity, $activity_content, Request $request) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
    
            try {
                $activity_content_criteria_id = $request->input('activity_content_criteria_id');
                $learner_activity_criteria_score_id = $request->input('learner_activity_criteria_score_id');
                $score = $request->input('score');
                $currentUrl = $request->input('currentUrl');
    
                DB::table('learner_activity_criteria_score')
                    ->where('activity_content_criteria_id', $activity_content_criteria_id)
                    ->where('learner_activity_criteria_score_id', $learner_activity_criteria_score_id)
             
                    ->update([
                        'score' => $score,
                    ]);


                // $learnerActivityOutputData = DB::table('learner_activity_output')
                // ->select(
                //     'learner_activity_output_id',
                //     'learner_course_id',
                //     'activity_id',
                //     'syllabus_id',
                //     'activity_content_id',
                //     'course_id'
                // )
                // ->where('learner_activity_output_id', $learner_activity_output)
                // ->where('learner_course_id', $learner_course)
                // ->where('activity_id', $activity)
                // ->where('activity_content_id', $activity_content)
                // ->first();

                
           

                    session()->flash('message', 'Output Scored Successfully');

                $response = [
                    'message' => 'Output Scored Successfully',
                    'redirect_url' => $currentUrl,
                ];
        
                return response()->json($response);
    
            } catch(\Exception $e) {
                dd($e->getMessage());
            }
    
        } else {
            return redirect('/instructor');
        }

    }

    public function view_quiz(Course $course, Syllabus $syllabus, $topic_id) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {       

                if (!function_exists('getRandomColor')) {
                    function getRandomColor() {
                    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                    }
                }
                
                // Generate a random color for mainBackgroundCol
                $mainBackgroundCol = getRandomColor();
    
                // Darken the mainBackgroundCol
                $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
    
                // Darken the mainBackgroundCol further for darkenedColor
                $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);


                $quizInfo = DB::table('quizzes')
                    ->select(
                        'quiz_id',
                        'course_id',
                        'syllabus_id',
                        'topic_id',
                        'quiz_title',
                        'duration',
                    )
                    ->where('course_id', $course->course_id)
                    ->where('syllabus_id', $syllabus->syllabus_id)
                    ->where('topic_id', $topic_id)
                    ->first();

                    $quizReference = DB::table('quiz_reference')
                    ->select(
                        'quiz_reference.quiz_reference_id',
                        'quiz_reference.quiz_id',
                        'quiz_reference.course_id',
                        'quiz_reference.syllabus_id',

                        'syllabus.topic_title'
                    )
                    ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                    ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                    ->get();

                $response = $this->course_content($course);

                session(['quiz_data' => [
                    'quizInfo' => $quizInfo,
                    'quizReference' => $quizReference,
                    // 'activityContent' => $activityContent,
                    // 'activityContentCriteria' => $activityContentCriteria,
                    'courseData' => $response,
                    'instructor' => $instructor,
                    'title' => 'Course Quiz',
                ]]);

                    $data = [
                        'title' => 'Course Quiz',
                        'mainBackgroundCol' => $mainBackgroundCol,
                        'scripts' => ['instructor_quiz_manage.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'quizInfo' => $quizInfo,
                        'quizReference' => $quizReference,
                        // 'instructor' => $response['instructor'],
                    ];

                    // dd($data);
            
                return view('instructor_course.courseQuizOverview', compact('instructor'))->with($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }


            }
        } else {
            return redirect('/instructor');
        }
    }

    public function quiz_info_json (Course $course, Syllabus $syllabus, $topic_id) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {       

                if (!function_exists('getRandomColor')) {
                    function getRandomColor() {
                    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                    }
                }
                
                // Generate a random color for mainBackgroundCol
                $mainBackgroundCol = getRandomColor();
    
                // Darken the mainBackgroundCol
                $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
    
                // Darken the mainBackgroundCol further for darkenedColor
                $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);


                $quizInfo = DB::table('quizzes')
                    ->select(
                        'quiz_id',
                        'course_id',
                        'syllabus_id',
                        'topic_id',
                        'quiz_title',
                        'duration',
                    )
                    ->where('course_id', $course->course_id)
                    ->where('syllabus_id', $syllabus->syllabus_id)
                    ->where('topic_id', $topic_id)
                    ->first();

                    $quizReference = DB::table('quiz_reference')
                    ->select(
                        'quiz_reference.quiz_reference_id',
                        'quiz_reference.quiz_id',
                        'quiz_reference.course_id',
                        'quiz_reference.syllabus_id',

                        'syllabus.syllabus_id',
                        'syllabus.topic_title'
                    )
                    ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                    ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                    ->get();


                    $syllabusData = DB::table('syllabus')
                    ->select(
                        "syllabus_id",
                        "topic_id",
                        "course_id",
                        "topic_title",
                        "category"
                    )
                    ->where('course_id', $course->course_id)
                    ->where('category', 'LESSON')
                    ->orderBy('topic_id', 'ASC')
                    ->get();

                    $learnerQuizOutputs = DB::table('learner_quiz_progress')
                    ->select(
                        'learner_quiz_progress.learner_quiz_progress_id',
                        'learner_quiz_progress.learner_course_id',
                        'learner_quiz_progress.learner_id',
                        'learner_quiz_progress.course_id',
                        'learner_quiz_progress.syllabus_id',
                        'learner_quiz_progress.quiz_id',
                        'learner_quiz_progress.attempt',
                        'learner_quiz_progress.score',
                        'learner_quiz_progress.remarks',
                        'learner_quiz_progress.updated_at',

                        'learner.learner_fname',
                        'learner.learner_lname',

                        DB::raw('COUNT(quiz_content.question_id) as question_count') // Use COUNT function here
                    )
                    ->join('learner', 'learner_quiz_progress.learner_id', '=', 'learner.learner_id')
                    ->leftJoin('quiz_content', 'quiz_content.quiz_id', '=', 'learner_quiz_progress.quiz_id')
                    ->where('learner_quiz_progress.course_id', $course->course_id)
                    ->where('learner_quiz_progress.syllabus_id', $syllabus->syllabus_id)
                    ->where('learner_quiz_progress.quiz_id', $quizInfo->quiz_id)
                    ->groupBy(
                        'learner_quiz_progress.learner_quiz_progress_id',
                        'learner_quiz_progress.learner_course_id',
                        'learner_quiz_progress.learner_id',
                        'learner_quiz_progress.course_id',
                        'learner_quiz_progress.syllabus_id',
                        'learner_quiz_progress.quiz_id',
                        'learner_quiz_progress.attempt',
                        'learner_quiz_progress.score',
                        'learner_quiz_progress.remarks',
                        'learner_quiz_progress.updated_at',
                        'learner.learner_fname',
                        'learner.learner_lname'
                    )
                    ->get();


                $response = $this->course_content($course);

                session(['quiz_data' => [
                    'quizInfo' => $quizInfo,
                    'quizReference' => $quizReference,
                    'syllabusData' => $syllabusData,
                    // 'activityContent' => $activityContent,
                    // 'activityContentCriteria' => $activityContentCriteria,
                    'courseData' => $response,
                    'instructor' => $instructor,
                    'title' => 'Course Quiz',
                ]]);

                    $data = [
                        'title' => 'Course Quiz',
                        'mainBackgroundCol' => $mainBackgroundCol,
                        'scripts' => ['instructor_quiz_manage.js'],
                        'lessonCount' => $response['lessonCount'],
                        'activityCount' => $response['activityCount'],
                        'quizCount' => $response['quizCount'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'quizInfo' => $quizInfo,
                        'quizReference' => $quizReference,
                        'syllabusData' => $syllabusData,
                        'learnerQuizOutputData' => $learnerQuizOutputs,
                    ];

                    // dd($data);
        return response()->json($data);

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            return response()->json(['errors' => $errors], 422);
        }
    }

    } else {
        return redirect('/instructor');
    }
 
    }

    public function manage_add_reference (Course $course, Syllabus $syllabus, $topic_id, Quizzes $quiz, Request $request) {
        try {
            $newReference = $request->validate([
                'quiz_id' => ['required'],
                'course_id' => ['required'],
                'syllabus_id' => ['required'],
            ]);


            $quizReference = QuizReferences::create($newReference);

        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }


    public function manage_update_reference (Course $course, Syllabus $syllabus, $topic_id, Quizzes $quiz, Request $request) {
        try {
            $newReference = $request->validate([
                'quiz_id' => ['required'],
                'course_id' => ['required'],
                'syllabus_id' => ['required'],
            ]);

            QuizReferences::where('quiz_id', $request->quiz_id)
            ->delete();

            $quizReference = QuizReferences::create($newReference);


        }catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }
    }

    public function manage_update_duration(Course $course, Syllabus $syllabus, $topic_id, Quizzes $quiz, Request $request) {
        
        try {

            $duration = $request->input('duration_ms');

            DB::table('quizzes')
            ->where('quiz_id', $quiz->quiz_id)
            ->where('course_id' , $course->course_id)
            ->where('syllabus_id', $syllabus->syllabus_id)
            ->update([
                'duration' => $duration,
            ]);

            $data = [
                'message' => 'quiz duration successfully updated'
            ];

            return response()->json($data);

        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
        
            return response()->json(['errors' => $errors], 422);
        }

    }

    public function quiz_content (Course $course, Syllabus $syllabus, $topic_id, Quizzes $quiz) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
    
            try {
                if (!function_exists('getRandomColor')) {
                    function getRandomColor() {
                    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                    }
                }
                
                // Generate a random color for mainBackgroundCol
                $mainBackgroundCol = getRandomColor();
    
                // Darken the mainBackgroundCol
                $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
    
                // Darken the mainBackgroundCol further for darkenedColor
                $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);

                $quizInfo = DB::table('quizzes')
                ->select(
                    'quiz_id',
                    'course_id',
                    'syllabus_id',
                    'topic_id',
                    'quiz_title',
                )
                ->where('course_id', $course->course_id)
                ->where('syllabus_id', $syllabus->syllabus_id)
                ->where('topic_id', $topic_id)
                ->first();

                $quizReference = DB::table('quiz_reference')
                ->select(
                    'quiz_reference.quiz_reference_id',
                    'quiz_reference.quiz_id',
                    'quiz_reference.course_id',
                    'quiz_reference.syllabus_id',

                    'syllabus.topic_title'
                )
                ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                ->get();


                $questionsData = DB::table('questions')
                ->select(
                    'questions.question_id',
                    'questions.syllabus_id',
                    'questions.course_id',
                    'questions.question',
                    'questions.category',

                    'question_answer.answer',
                    'question_answer.isCorrect'
                )
                ->join('question_answer', 'question_answer.question_id', '=', 'questions.question_id')
                ->where('questions.course_id', $course->course_id)
                ->get();

             




            $response = $this->course_content($course);

            session(['quiz_data' => [
                'quizInfo' => $quizInfo,
                'quizReference' => $quizReference,
                'courseData' => $response,
                'instructor' => $instructor,
                'title' => 'Course Quiz',
            ]]);

                $data = [
                    'title' => 'Course Quiz',
                    'mainBackgroundCol' => $mainBackgroundCol,
                    'scripts' => ['instructor_quiz_builder.js'],
                    'lessonCount' => $response['lessonCount'],
                    'activityCount' => $response['activityCount'],
                    'quizCount' => $response['quizCount'],
                    'course' => $response['course'],
                    'syllabus' => $response['syllabus'],
                    'quizInfo' => $quizInfo,
                    'quizReference' => $quizReference,
                    'questionsData' => $questionsData,
                    // 'instructor' => $response['instructor'],
                ];


                    // dd($data);
            
                return view('instructor_course.courseQuizContent', compact('instructor'))->with($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        
        } else {
            return redirect('/instructor');
        }
    }


    public function quiz_content_json (Course $course, Syllabus $syllabus, $topic_id, Quizzes $quiz) {
        if (auth('instructor')->check()) {
            $instructor = session('instructor');
    
            try {
                if (!function_exists('getRandomColor')) {
                    function getRandomColor() {
                    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                    }
                }
                
                // Generate a random color for mainBackgroundCol
                $mainBackgroundCol = getRandomColor();
    
                // Darken the mainBackgroundCol
                $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
    
                // Darken the mainBackgroundCol further for darkenedColor
                $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);

                $quizInfo = DB::table('quizzes')
                ->select(
                    'quiz_id',
                    'course_id',
                    'syllabus_id',
                    'topic_id',
                    'quiz_title',
                )
                ->where('course_id', $course->course_id)
                ->where('syllabus_id', $syllabus->syllabus_id)
                ->where('topic_id', $topic_id)
                ->first();

                $quizReference = DB::table('quiz_reference')
                ->select(
                    'quiz_reference.quiz_reference_id',
                    'quiz_reference.quiz_id',
                    'quiz_reference.course_id',
                    'quiz_reference.syllabus_id',

                    'syllabus.topic_title'
                )
                ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                ->get();

                $quizContentData = DB::table('quiz_content')
                ->select(
                    'quiz_content.quiz_content_id',
                    // 'quiz_content.syllabus_id',
                    'quiz_content.course_id',
                    'quiz_content.question_id',
                    'questions.syllabus_id',
                    'questions.question',
                    'questions.category',
                    'syllabus.topic_title',
                    DB::raw('JSON_ARRAYAGG(question_answer.answer) as answers'),
                    DB::raw('JSON_ARRAYAGG(question_answer.isCorrect) as isCorrect')
                )
                ->join('questions', 'questions.question_id', '=', 'quiz_content.question_id')
                ->join('syllabus', 'syllabus.syllabus_id', '=', 'questions.syllabus_id')
                ->leftJoin('question_answer', 'question_answer.question_id', '=', 'quiz_content.question_id')
                ->where('quiz_content.course_id', $course->course_id)
                ->groupBy(
                    'quiz_content.quiz_content_id',
                    'quiz_content.syllabus_id',
                    'quiz_content.course_id',
                    'quiz_content.question_id',
                    'questions.question',
                    'questions.category',
                    'syllabus.topic_title'
                )
                ->get();



                $questionsData = DB::table('questions')
                ->select(
                    'questions.question_id',
                    'questions.syllabus_id',
                    'questions.course_id',
                    'questions.question',
                    'questions.category',
                    'syllabus.topic_title',
                    DB::raw('JSON_ARRAYAGG(question_answer.answer) as answers'),
                    DB::raw('JSON_ARRAYAGG(question_answer.isCorrect) as isCorrect')
                )
                ->join('syllabus', 'syllabus.syllabus_id' , '=', 'questions.syllabus_id')
                ->leftJoin('question_answer', 'question_answer.question_id', '=', 'questions.question_id')
                ->where('questions.course_id', $course->course_id)
                ->groupBy('questions.question_id')
                ->get();


             




            $response = $this->course_content($course);

            session(['quiz_data' => [
                'quizInfo' => $quizInfo,
                'quizReference' => $quizReference,
                'courseData' => $response,
                'instructor' => $instructor,
                'title' => 'Course Quiz',
            ]]);

                $data = [
                    'title' => 'Course Quiz',
                    'mainBackgroundCol' => $mainBackgroundCol,
                    'scripts' => ['instructor_quiz_builder.js'],
                    'lessonCount' => $response['lessonCount'],
                    'activityCount' => $response['activityCount'],
                    'quizCount' => $response['quizCount'],
                    'course' => $response['course'],
                    'syllabus' => $response['syllabus'],
                    'quizInfo' => $quizInfo,
                    'quizReference' => $quizReference,
                    'questionsData' => $questionsData,
                    'quizContent' => $quizContentData,
                    // 'instructor' => $response['instructor'],
                ];


                    // dd($data);
                    

                return response()->json($data);
            
                // return view('instructor_course.courseQuizContent', compact('instructor'))->with($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }
        
        } else {
            return redirect('/instructor');
        }
    }

    public function empty_quiz_question(Course $course, Syllabus $syllabus, $topic_id, $quiz_id)
    {
        try {
            DB::beginTransaction();

            DB::table('quiz_content')
                ->where('quiz_id', $quiz_id)
                ->delete();

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'An error occurred while emptying quiz content.']);
        }
    }

    public function add_quiz_question(Course $course, Syllabus $syllabus, $topic_id, $quiz_id, Request $request)
    {
        try {
            // DB::beginTransaction();

            $questionData = [
                'syllabus_id' => $request->input('syllabus_id'),
                'course_id' => $request->input('course_id'),
                'question' => $request->input('question'),
                'category' => $request->input('category'),
            ];

            $questions = Questions::create($questionData);

            $answersData = json_decode($request->input('answer'));
            $isCorrectData = json_decode($request->input('isCorrect'));

            foreach ($answersData as $index => $answer) {
                $questionAnswerData = [
                    'question_id' => $questions->question_id,
                    'answer' => $answer,
                    'isCorrect' => $isCorrectData[$index],
                ];

                QuestionAnswers::create($questionAnswerData);
            }

            $quizContentData = [
                'quiz_id' => $quiz_id,
                'syllabus_id' => $syllabus->syllabus_id,
                'course_id' => $course->course_id,
                'question_id' => $questions->question_id,
            ];

            QuizContents::create($quizContentData);

            // DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in add_quiz_question: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'An error occurred while adding quiz question: ' . $e->getMessage()]);
        }
    }

    public function update_quiz_question(Course $course, Syllabus $syllabus, $topic_id, $quiz_id, Request $request)
    {
        try {
            // DB::beginTransaction();

            $question_id = $request->input('question_id');

            DB::table('questions')
                ->where('question_id', $question_id)
                ->where('course_id', $course->course_id)
                ->update([
                    'syllabus_id' => $request->input('syllabus_id'),
                    'course_id' => $request->input('course_id'),
                    'question' => $request->input('question'),
                    'category' => $request->input('category'),
                ]);

            DB::table('question_answer')
                ->where('question_id', $question_id)
                ->delete();

            $answersData = json_decode($request->input('answer'));
            $isCorrectData = json_decode($request->input('isCorrect'));

            foreach ($answersData as $index => $answer) {
                $questionAnswerData = [
                    'question_id' => $question_id,
                    'answer' => $answer,
                    'isCorrect' => $isCorrectData[$index],
                ];

                QuestionAnswers::create($questionAnswerData);
            }

            $quizContentData = [
                'quiz_id' => $quiz_id,
                'syllabus_id' => $syllabus->syllabus_id,
                'course_id' => $course->course_id,
                'question_id' => $question_id,
            ];

            QuizContents::create($quizContentData);

            // DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in update_quiz_question: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'An error occurred while updating quiz question: ' . $e->getMessage()]);
        }
    }


    public function view_learner_output(Course $course, Syllabus $syllabus, $topic_id, LearnerQuizProgress $learner_quiz_progress) {

        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {  

                    if (!function_exists('getRandomColor')) {
                        function getRandomColor() {
                        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Generate a random color for mainBackgroundCol
                    $mainBackgroundCol = getRandomColor();
        
                    // Darken the mainBackgroundCol
                    $mainColorRGB = sscanf($mainBackgroundCol, "#%02x%02x%02x");
                    $mainBackgroundCol = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.6, $mainColorRGB[1] * 0.6, $mainColorRGB[2] * 0.6);
        
                    // Darken the mainBackgroundCol further for darkenedColor
                    $darkenedColor = sprintf("#%02x%02x%02x", $mainColorRGB[0] * 0.4, $mainColorRGB[1] * 0.4, $mainColorRGB[2] * 0.4);

                    $quizInfo = DB::table('quizzes')
                    ->select(
                        'quiz_id',
                        'course_id',
                        'syllabus_id',
                        'topic_id',
                        'quiz_title',
                        'duration',
                    )
                    ->where('course_id', $course->course_id)
                    ->where('syllabus_id', $syllabus->syllabus_id)
                    ->where('topic_id', $topic_id)
                    ->first();

                    $quizReference = DB::table('quiz_reference')
                    ->select(
                        'quiz_reference.quiz_reference_id',
                        'quiz_reference.quiz_id',
                        'quiz_reference.course_id',
                        'quiz_reference.syllabus_id',

                        'syllabus.topic_title'
                    )
                    ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                    ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                    ->get();

                    $response = $this->course_content($course);

                    $learnerQuizProgressData = DB::table('learner_quiz_progress')
                    ->select(
                        'learner_quiz_progress.learner_quiz_progress_id',
                        'learner_quiz_progress.learner_course_id',
                        'learner_quiz_progress.learner_id',
                        'learner_quiz_progress.syllabus_id',
                        'learner_quiz_progress.quiz_id',
                        'learner_quiz_progress.status',
                        'learner_quiz_progress.max_attempt',
                        'learner_quiz_progress.attempt',
                        'learner_quiz_progress.score',
                        'learner_quiz_progress.remarks',
                        'learner_quiz_progress.updated_at',
                        

                        'learner.learner_fname',
                        'learner.learner_lname',
                    )
                    ->join('learner', 'learner_quiz_progress.learner_id', '=', 'learner.learner_id')
                    ->where('learner_quiz_progress.learner_quiz_progress_id', $learner_quiz_progress->learner_quiz_progress_id)
                    ->where('learner_quiz_progress.course_id', $course->course_id)
                    ->where('learner_quiz_progress.syllabus_id', $syllabus->syllabus_id)
                    ->where('learner_quiz_progress.quiz_id', $quizInfo->quiz_id)
                    ->first();
                    

                    $quizQuestionTotalCount = DB::table('learner_quiz_output')
                    ->select(
                        'learner_quiz_output',
                        'quiz_content_id',
                        'quiz_id',
                        'attempts',
                    )
                    ->where('learner_course_id', $learnerQuizProgressData->learner_course_id)
                    ->where('course_id', $course->course_id)
                    ->where('syllabus_id', $syllabus->syllabus_id)
                    ->where('quiz_id', $quizInfo->quiz_id)
                    ->where('attempts', 1)
                    ->count();



                    $data = [
                        'title' => 'Course Quiz',
                        'mainBackgroundCol' => $mainBackgroundCol,
                        'scripts' => ['instructor_quiz_learnerResponse.js'],
                        'course' => $response['course'],
                        'syllabus' => $response['syllabus'],
                        'quizData' => $quizInfo,
                        'quizReferenceData' => $quizReference,
                        'learnerQuizOutputData' => $learnerQuizProgressData,
                        'quizQuestionTotalCount' => $quizQuestionTotalCount,
                    ];

                    // dd($data);
            
                return view('instructor_course.courseQuiz_viewLearnerOutput', compact('instructor'))->with($data);

            } catch (\Exception $e) {
                dd($e->getMessage());
            }


            }
        } else {
            return redirect('/instructor');
        }

    }
    


    public function view_learner_output_json(Course $course, Syllabus $syllabus, $topic_id, LearnerQuizProgress $learner_quiz_progress) {

        if (auth('instructor')->check()) {
            $instructor = session('instructor');
            
            $instructor = session('instructor');
            if($instructor['status'] !== 'Approved') {
                session()->flash('message', 'Account is not yet Approved');
                return response()->json(['message' => 'Account is not yet Approved', 'redirect_url' => '/instructor/courses']);
            } else {
                try {  

                    $quizInfo = DB::table('quizzes')
                    ->select(
                        'quiz_id',
                        'course_id',
                        'syllabus_id',
                        'topic_id',
                        'quiz_title',
                        'duration',
                    )
                    ->where('course_id', $course->course_id)
                    ->where('syllabus_id', $syllabus->syllabus_id)
                    ->where('topic_id', $topic_id)
                    ->first();

                    $quizReference = DB::table('quiz_reference')
                    ->select(
                        'quiz_reference.quiz_reference_id',
                        'quiz_reference.quiz_id',
                        'quiz_reference.course_id',
                        'quiz_reference.syllabus_id',

                        'syllabus.topic_title'
                    )
                    ->join('syllabus', 'syllabus.syllabus_id' , '=', 'quiz_reference.syllabus_id')
                    ->where('quiz_reference.quiz_id' , $quizInfo->quiz_id)
                    ->get();

                    $learnerQuizProgressData = DB::table('learner_quiz_progress')
                    ->select(
                        'learner_quiz_progress.learner_quiz_progress_id',
                        'learner_quiz_progress.learner_course_id',
                        'learner_quiz_progress.syllabus_id',
                        'learner_quiz_progress.course_id',
                        'learner_quiz_progress.quiz_id',
                        'learner_quiz_progress.status',
                        'learner_quiz_progress.max_attempt',
                        'learner_quiz_progress.attempt',
                        'learner_quiz_progress.score',
                        'learner_quiz_progress.remarks',
                        'learner_quiz_progress.updated_at',
                    )
                    ->where('learner_quiz_progress.learner_quiz_progress_id', $learner_quiz_progress->learner_quiz_progress_id)
                    ->where('learner_quiz_progress.course_id', $course->course_id)
                    ->where('learner_quiz_progress.syllabus_id', $syllabus->syllabus_id)
                    ->where('learner_quiz_progress.quiz_id', $quizInfo->quiz_id)
                    ->first();
                    
                    $correctAnswerSubquery = DB::table('question_answer')
                    ->select('question_id', DB::raw('JSON_ARRAYAGG(answer) as correct_answer'))
                    ->where('isCorrect', 1)
                    ->groupBy('question_id');

                $learnerQuizData = DB::table('learner_quiz_output')
                    ->select(
                        'learner_quiz_output.learner_quiz_output_id',
                        'learner_quiz_output.quiz_id',
                        'learner_quiz_output.quiz_content_id',
                        'learner_quiz_output.attempts',
                        'learner_quiz_output.answer',
                        'learner_quiz_output.isCorrect',
                        'quiz_content.course_id',
                        'quiz_content.question_id',
                        'questions.syllabus_id',
                        'questions.question',
                        'questions.category',
                        DB::raw('JSON_ARRAYAGG(question_answer.answer) as all_choices'),
                        DB::raw('correct_answers.correct_answer')
                    )
                    ->join('quiz_content', 'learner_quiz_output.quiz_content_id', '=', 'quiz_content.quiz_content_id')
                    ->join('questions', 'quiz_content.question_id', '=', 'questions.question_id')
                    ->leftJoinSub($correctAnswerSubquery, 'correct_answers', function ($join) {
                        $join->on('questions.question_id', '=', 'correct_answers.question_id');
                    })
                    ->leftJoin('question_answer', 'questions.question_id', '=', 'question_answer.question_id')
                    ->where('learner_quiz_output.attempts', $learnerQuizProgressData->attempt)
                    ->where('learner_quiz_output.learner_course_id', $learnerQuizProgressData->learner_course_id)
                    ->where('quiz_content.quiz_id', $learnerQuizProgressData->quiz_id)
                    ->where('quiz_content.course_id', $learnerQuizProgressData->course_id)
                    ->where('quiz_content.syllabus_id', $learnerQuizProgressData->syllabus_id)
                    ->groupBy(
                        'learner_quiz_output.learner_quiz_output_id',
                        'learner_quiz_output.quiz_content_id',
                        'quiz_content.course_id',
                        'quiz_content.question_id',
                        'questions.syllabus_id',
                        'questions.question',
                        'questions.category',
                        'correct_answers.correct_answer'
                    )
                    ->get();



                    $data = [
                        'title' => 'Course Quiz',
                        'scripts' => ['instructor_quiz_learnerResponse.js'],
                        'quizReferenceData' => $quizReference,
                        'learnerQuizData' => $learnerQuizProgressData,
                        'learnerQuizOutputData' => $learnerQuizData,

                    ];

                    // dd($data);

                    return response()->json($data);

            } catch (ValidationException $e) {
                $errors = $e->validator->errors();
        
                return response()->json(['errors' => $errors], 422);
            }


            }
        } else {
            return redirect('/instructor');
        }

    }



}