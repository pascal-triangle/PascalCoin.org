<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Http\Requests\ContactRequest;
use App\Projects;
use Illuminate\Http\Request;
use Spatie\Newsletter\Newsletter;

/**
 * Class IndexController
 *
 * Basic controller to handle unauthenticated actions.
 */
class IndexController extends Controller
{
    /**
     * Shows the home page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function home()
    {
        return view('home');
    }

    /**
     * Shows the whitepaper page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function whitepapers()
    {
        return view('whitepapers');
    }

    /**
     * Shows the get started page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getStarted()
    {
        return view('get_started');
    }

    /**
     * Shows the voting page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function voting()
    {
        return view('voting');
    }

    /**
     * Shows the contact page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function contact()
    {
        return view('contact');
    }

    /**
     * Displays the list of projects.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function projects()
    {
        return view('projects', [
            'projects' => Projects::orderBy('title', 'ASC')->get()
        ]);
    }

    /**
     * Displays a single project.
     *
     * @param Projects $project
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function project(Projects $project)
    {
        return view('project', [
            'project' => $project,
            'markdown' =>  \Parsedown::instance()->parse($project->description)
        ]);
    }

    /**
     * Handles the submit of the contact form.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactSubmit(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->getMessageBag()->messages()
            ]);
        }

        $contact = Contact::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'message' => $request->get('message'),
        ]);

        \Mail::send(new \App\Mail\Contact($contact));

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Handles the request to subscribe to the newsletter.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newsletterSubmit(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->getMessageBag()->messages()
            ]);
        }

        \Newsletter::subscribe($request->get('email'), [
            'firstName' => $request->get('name')
        ], getenv('MAILCHIMP_LIST'));

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Shows a list of all PIPs.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pips()
    {
        $pips = include storage_path('app/PIP/database.php');
        return view('pips', ['pips' => array_reverse($pips)]);
    }

    /**
     * Shows a single PIP.
     *
     * @param Request $request
     * @param int $pip
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pip(Request $request, int $pip)
    {
        $pips = include storage_path('app/PIP/database.php');
        $pipData = [];
        $pipText = '';
        foreach($pips as $pipItem)
        {
            if($pipItem['pip_no'] !== $pip) {
                continue;
            }
            $pipData = $pipItem;
            $pipText = \Parsedown::instance()->parse(\Storage::get('PIP/' . $pipData['pip'] . '.md'));

            // rewrite resource links
            $pipText = str_replace('resources/PIP-', asset('storage/PIP/resources/PIP-'), $pipText);

            break;
        }

        if(count($pipData) === 0) {
            // TODO: 404
            exit;
        }

        return view('pip', [
            'pip' => $pipText,
            'pipData' => $pipData
        ]);
    }

    /**
     * Display the JSON RPC api documentation.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function rpc(Request $request)
    {
        $rpc = \Parsedown::instance()->parse(\Storage::get('RPC.md'));
        return view('rpc', ['rpc' => $rpc]);
    }

    public function fundingTransparency()
    {
        return view('funding_transparency');
    }
}