<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\QuestionBank;
use App\Models\AnswerBank;
use App\Models\Districts;
use App\Models\Audit;
use App\Models\Feedback;
use App\Models\Student_Lesson;
use App\Models\Student_Quiz;
use App\Models\Student_Game;
use App\Models\Leaderboard;
use App\Models\Student;
use DB;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

ini_set('memory_limit', '-1');

class AdminController extends Controller
{
    public function index()
    {
        $totalUser = User::get()->count();
        $totalStudent = User::where('is_student', '1')->get()->count();
        $totalStaffecerd = User::where('is_ecerdb_personnel', '1')->get()->count();
        $totalAdmin = User::where('is_admin', '1')->get()->count();


        $student = DB::table('students')->select(DB::raw('IFNULL(sum(students_games.student_point),0) + IFNULL(leaderboards.scores, 0) as total_points, students.id as id, users.profile_picture as profile_picture, users.name as name, users.school as school, users.state as state'))
            ->leftJoin("students_games", "students_games.student_id", "=", "students.id")
            ->leftJoin("leaderboards", "leaderboards.student_id", "=", "students.id")
            ->join("users", "users.id", "=", "students.user_id")
            ->groupBy("students.id", "leaderboards.scores")
            ->orderBy("total_points", "DESC")
            // ->limit(10)
            // ->get();
            ->paginate(10);

        $all_students =  DB::table('students')->select(DB::raw('IFNULL(sum(students_games.student_point),0) + IFNULL(leaderboards.scores, 0) as total_points, users.id as user_id'))
            ->leftJoin("students_games", "students_games.student_id", "=", "students.id")
            ->leftJoin("leaderboards", "leaderboards.student_id", "=", "students.id")
            ->join("users", "users.id", "=", "students.user_id")
            ->groupBy("students.id", "leaderboards.scores")
            ->orderBy("total_points", "DESC")
            ->get();

        $current_user_ranking = 0;

        for ($i = 0; $i < count($all_students); $i++) {
            if ($all_students[$i]->user_id == Auth::user()->id) {
                $current_user_ranking = $i + 1;
            }

            return view('home-admin', compact('totalUser', 'totalStudent', 'totalStaffecerd', 'totalAdmin', 'student', 'current_user_ranking'));
        }
    }


    public function viewEditProfile()
    {
        $user_id = Auth::user()->id;
        $user = User::findorFail($user_id);
        $district = Districts::get();

        return view('admin.profiles.edit-profile', compact('user', 'district'));
    }

    public function updatingProfile()
    {
        // dd(request()->all());
        $this->validator(request()->all())->validate();



        $user_id = Auth::user()->id;
        $user = User::findOrFail($user_id);

        $user->name = request()->name;
        $user->ic_number = request()->ic_number;
        $user->email = request()->email;
        $user->phone = request()->phone;
        $user->address = request()->address;
        $user->postcode = request()->postcode;
        $user->state = request()->state;
        $user->district = request()->district;

        //upload profile picture
        $gambar_profile = "";

        if ($files = request()->file('gambar_profile') != null) {
            $gambar_profile = request()->file('gambar_profile')->store('public/uploads/user_pictures');
            $user->profile_picture = $gambar_profile;
        }

        $user->save();

        return redirect()->route('admin.profiles.edit-profile')->with("success", "Profil pengguna telah dikemaskini");
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string'],
            'ic_number' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'address' => ['required', 'string'],
            'postcode' => ['required'],
            'state' => ['required', 'string'],
        ]);
    }

    public function viewChangePassword()
    {
        return view('admin.profiles.change-password');
    }

    public function updatingPassword(Request $request)
    {
        $user_id = Auth::user()->id;
        $user = User::findOrFail($user_id);

        if (!(Hash::check($request->get('password'), Auth::user()->password))) {
            return redirect()->back()->with("error", "Kata laluan semasa tidak betul");
        }

        if (strcmp($request->get('password'), $request->get('new_password')) == 0) {
            return redirect()->back()->with("error", "Kata laluan semasa tidak boleh sama dengan kata laluan baru");
        }

        if (strcmp($request->get('new_password'), $request->get('password_confirmation')) == 1) {
            return redirect()->back()->with("error", "Kata laluan baru tidak sama dengan kata laluan pengesahan");
        }

        $validatedData = $request->validate([
            'password' => 'required',
            'new_password' => 'required|string|min:6',
        ]);

        $hashed_random_password = Hash::make($request->get('new_password'));

        $user->password = $hashed_random_password;

        $user->save();

        return redirect()->route('admin.profiles.change-password')->with("success", "Kata laluan berjaya dikemaskini");
    }

    public function viewUserList()
    {
        $user_list = User::with('student')->get();
        // dd($user_list);
        foreach ($user_list as $data) {
            # code...
            if ($data->student) {
                $lesson_progress = Student_Lesson::where('student_id', $data->student->id)->count();
                $lesson_progress = ($lesson_progress / 10) * 100;

                $quiz_progress = Student_Quiz::where('result_status', "Lulus")->where('student_id', $data->student->id)->distinct('quiz_id')->count();
                $quiz_progress = ($quiz_progress / 10) * 100;

                $game_progress = Student_Game::where('student_id', $data->student->id)->distinct('game_id')->count();
                $game_progress = ($game_progress / 10) * 100;

                $data->lesson_progress = $lesson_progress;
                $data->quiz_progress = $quiz_progress;
                $data->game_progress = $game_progress;
                $total_progress = ((($data->lesson_progress + $data->quiz_progress + $data->game_progress) / 3) / 100) * 100;
                $data->total_progress = number_format($total_progress ?? 0, 2);
                $data->save();
            }
        }
        // dd($user_list);
        return view('admin.others.user-list.list', compact('user_list'));
    }

    public function editUserList($id)
    {
        $user = User::get();
        $user = User::findOrFail($id);
        $district = Districts::get();
        // dd($user);
        return view('admin.others.user-list.edit', compact('user', 'district'));
    }

    public function updateUserList(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $user->name = $request->name;
        $user->ic_number = $request->ic_number;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->school = $request->school;
        $user->address = $request->address;
        $user->postcode = $request->postcode;
        $user->state = $request->state;
        $user->district = $request->district;

        //updating role
        if ($request->role == "Admin") {
            $user->is_admin = 1;
            $user->is_ecerdb_personnel = 0;
            $user->is_student = 0;
        } elseif ($request->role == "ECERDB") {
            $user->is_admin = 0;
            $user->is_ecerdb_personnel = 1;
            $user->is_student = 0;
        } else {
            $user->is_admin = 0;
            $user->is_ecerdb_personnel = 0;
            $user->is_student = 1;
        }

        $user->save();

        return redirect()->route('admin.others.user-list.list')->with("success", "Maklumat pengguna telah dikemaskini");
    }

    public function disableStatus(Request $request)
    {
        $user = User::findOrFail($request->id_disable);
        $user->is_active = 0;
        $user->save();

        return redirect()->route('admin.others.user-list.list')->with("success", "Pengguna telah dinyahaktif");
    }

    public function activateStatus(Request $request)
    {
        $user = User::findOrFail($request->id_activate);
        $user->is_active = 1;
        $user->save();

        return redirect()->route('admin.others.user-list.list')->with("success", "Pengguna telah diaktifkan");
    }

    public function resetPasswordUser(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $hashed_random_password = Hash::make("1234567890");

        $user->password = $hashed_random_password;
        $user->save();

        return redirect()->route('admin.others.user-list.list')->with("success", "Reset has been successfully reset");
    }

    public function viewAuditList()
    {
        //   dd(now()->format('Y-m-d') . ' 00:00:00');
        $data = Audit::with('user')->where('created_at', '>', now()->format('Y-m-d') . ' 00:00:00')
            ->where('created_at', '<', now()->format('Y-m-d') . ' 23:59:59')
            ->get();

        //   dd($data);
        return view('admin.others.audit-trail.audit-trail-log', compact('data'));
    }

    public function viewAuditListFilter(Request $request)
    {
        $tarikh_mula = date($request->tarikh_mula);
        $tarikh_akhir = date($request->tarikh_akhir);

        // $data = Audit::whereBetween('created_at', [$tarikh_mula.' 00:00:00',$tarikh_akhir.' 23:59:59'])
        //             ->where('event', 'Log Masuk')
        //             ->orWhere('event', 'Log Keluar')
        //             ->orderBy('created_at', 'desc')
        //             ->get();


        $data = Audit::where('updated_at', '>', $tarikh_mula . ' 00:00:00')
            ->where('updated_at', '<', $tarikh_akhir . ' 23:59:59')
            ->orderBy('updated_at', 'asc')
            ->get();

        return view('admin.others.audit-trail.audit-trail-log-filter', compact('data'));
    }

    public function viewFeedbackList()
    {
        $feedback = Feedback::get();
        return view('admin.others.feedback.list', compact('feedback'));
    }

    public function viewStudentProgress()
    {

        $users = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.ic_number',
                'users.state',
                'users.district',
                'users.school',
                'users.lesson_progress',
                'users.quiz_progress',
                'users.game_progress',
                'students_lessons.lesson_id',
                'students_quizes.quiz_id',
                'students_quizes.result_status',
                'students_games.game_id'
            )
            ->join("students", "students.user_id", "=", "users.id")
            ->leftJoin("students_games", "students_games.student_id", "=", "students.id")
            ->leftJoin("students_lessons", "students_lessons.student_id", "=", "students.id")
            ->leftJoin("students_quizes", "students_quizes.student_id", "=", "students.id")
            ->paginate(100);

        // dd($users);
        return view('admin.others.user-list.progress', compact('users'));

    }

    public function viewGraph()
    {
        $student_active = User::where('is_student', 1)->where('is_active', 1)->count();
        $student_inactive = User::where('is_student', 1)->where('is_active', 0)->count();

        $student_pass = Student_Quiz::where('result_status', 'Lulus')->count();
        $student_fail = Student_Quiz::where('result_status', 'Gagal')->count();

        $lesson1 = Student_Lesson::where('lesson_id', 1)->count();
        $lesson3 = Student_Lesson::where('lesson_id', 3)->count();
        $lesson4 = Student_Lesson::where('lesson_id', 4)->count();
        $lesson5 = Student_Lesson::where('lesson_id', 5)->count();
        $lesson6 = Student_Lesson::where('lesson_id', 6)->count();
        $lesson7 = Student_Lesson::where('lesson_id', 7)->count();
        $lesson8 = Student_Lesson::where('lesson_id', 8)->count();
        $lesson9 = Student_Lesson::where('lesson_id', 9)->count();
        $lesson10 = Student_Lesson::where('lesson_id', 10)->count();
        $lesson11 = Student_Lesson::where('lesson_id', 11)->count();

        $game1 = Student_Game::where('game_id', 1)->count();
        $game2 = Student_Game::where('game_id', 2)->count();
        $game3 = Student_Game::where('game_id', 3)->count();
        $game4 = Student_Game::where('game_id', 4)->count();
        $game5 = Student_Game::where('game_id', 5)->count();
        $game6 = Student_Game::where('game_id', 6)->count();
        $game7 = Student_Game::where('game_id', 7)->count();
        $game8 = Student_Game::where('game_id', 8)->count();
        $game9 = Student_Game::where('game_id', 9)->count();
        $game10 = Student_Game::where('game_id', 10)->count();

        $state1 = User::where('state', 'Perlis')->count();
        $state2 = User::where('state', 'Kedah')->count();
        $state3 = User::where('state', 'Pulau Pinang')->count();
        $state4 = User::where('state', 'Perak')->count();
        $state5 = User::where('state', 'Selangor')->count();
        $state6 = User::where('state', 'Negeri Sembilan')->count();
        $state7 = User::where('state', 'Melaka')->count();
        $state8 = User::where('state', 'Johor')->count();
        $state9 = User::where('state', 'Pahang')->count();
        $state10 = User::where('state', 'Kelantan')->count();
        $state11 = User::where('state', 'Terengganu')->count();
        $state12 = User::where('state', 'Sarawak')->count();
        $state13 = User::where('state', 'Sabah')->count();
        $state14 = User::where('state', 'WP Kuala Lumpur')->count();
        $state15 = User::where('state', 'WP Putrajaya')->count();



        return view('admin.profiles.graph', compact(
            'student_active',
            'student_inactive',
            'student_pass',
            'student_fail',
            'lesson1',
            'lesson3',
            'lesson4',
            'lesson5',
            'lesson6',
            'lesson7',
            'lesson8',
            'lesson9',
            'lesson10',
            'lesson11',
            'game1',
            'game2',
            'game3',
            'game4',
            'game5',
            'game6',
            'game7',
            'game8',
            'game9',
            'game10',
            'state1',
            'state2',
            'state3',
            'state4',
            'state5',
            'state6',
            'state7',
            'state8',
            'state9',
            'state10',
            'state11',
            'state12',
            'state13',
            'state14',
            'state15'
        ));
    }
}
