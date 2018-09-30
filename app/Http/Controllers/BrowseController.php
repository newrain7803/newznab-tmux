<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Blacklight\Releases;
use Illuminate\Http\Request;

class BrowseController extends BasePageController
{
    /**
     * @throws \Exception
     */
    public function index()
    {
        $this->setPrefs();
        $releases = new Releases(['Settings' => $this->settings]);

        $this->smarty->assign('category', -1);

        $orderby = '';
        $page = request()->has('page') && is_numeric(request()->input('page')) ? request()->input('page') : 1;
        $offset = ($page - 1) * config('nntmux.items_per_page');

        $rslt = $releases->getBrowseRange($page, [-1], $offset, config('nntmux.items_per_page'), $orderby, -1, $this->userdata['categoryexclusions'], -1);
        $results = $this->paginate($rslt ?? [], $rslt[0]->_totalcount ?? 0, config('nntmux.items_per_page'), $page, request()->url(), request()->query());

        $this->smarty->assign('catname', 'All');

        $this->smarty->assign('lastvisit', $this->userdata['lastlogin']);

        foreach ($results as $result) {
            $browse[] = $result;
        }

        $this->smarty->assign(
            [
                'results' => $results,
                'resultsadd' => $browse,
            ]
        );

        $meta_title = 'Browse All Releases';
        $meta_keywords = 'browse,nzb,description,details';
        $meta_description = 'Browse for Nzbs';

        $content = $this->smarty->fetch('browse.tpl');
        $this->smarty->assign(
            [
                'content' => $content,
                'meta_title' => $meta_title,
                'meta_keywords' => $meta_keywords,
                'meta_description' => $meta_description,
            ]
        );
        $this->pagerender();
    }

    /**
     * @param string $parentCategory
     * @param string $id
     * @throws \Exception
     */
    public function show(string $parentCategory, string $id = 'All')
    {
        $this->setPrefs();
        $releases = new Releases(['Settings' => $this->settings]);

        $parentId = Category::query()->where('title', $parentCategory)->first(['id']);

        $query = Category::query();
        if ($id !== 'All') {
            $query->where('title', $id)->where('parentid', $parentId['id']);
        } else {
            $query->where('id', $parentId['id']);
        }
        $category = $query->first(['id']) ?? -1;

        $grp = -1;

        $catarray = [];
        $catarray[] = $category['id'];

        $this->smarty->assign('parentcat', ucfirst($parentCategory));
        $this->smarty->assign('category', $category);

        $orderby = '';
        $page = request()->has('page') && is_numeric(request()->input('page')) ? request()->input('page') : 1;
        $offset = ($page - 1) * config('nntmux.items_per_page');

        $rslt = $releases->getBrowseRange($page, $catarray, $offset, config('nntmux.items_per_page'), $orderby, -1, $this->userdata['categoryexclusions'], $grp);
        $results = $this->paginate($rslt ?? [], $rslt[0]->_totalcount ?? 0, config('nntmux.items_per_page'), $page, request()->url(), request()->query());

        $browse = [];

        foreach ($results as $result) {
            $browse[] = $result;
        }

        $this->smarty->assign('catname', $id);

        $this->smarty->assign('lastvisit', $this->userdata['lastlogin']);

        $this->smarty->assign(
            [
                'results' => $results,
                'resultsadd' => $browse,
            ]
        );

        $covgroup = '';
        if ($category === -1 && $grp === -1) {
            $this->smarty->assign('catname', 'All');
        } elseif ($category !== -1 && $grp === -1) {
            $cdata = Category::find($category['id']);
            if ($cdata !== null) {
                if ($cdata->parentid === Category::GAME_ROOT || $cdata->id === Category::GAME_ROOT) {
                    $covgroup = 'console';
                } elseif ($cdata->parentid === Category::MOVIE_ROOT || $cdata->id === Category::MOVIE_ROOT) {
                    $covgroup = 'movies';
                } elseif ($cdata->parentid === Category::XXX_ROOT || $cdata->id === Category::XXX_ROOT) {
                    $covgroup = 'xxx';
                } elseif ($cdata->parentid === Category::PC_ROOT || $cdata->id === Category::PC_GAMES) {
                    $covgroup = 'games';
                } elseif ($cdata->parentid === Category::MUSIC_ROOT || $cdata->id === Category::MUSIC_ROOT) {
                    $covgroup = 'music';
                } elseif ($cdata->parentid === Category::BOOKS_ROOT || $cdata->id === Category::BOOKS_ROOT) {
                    $covgroup = 'books';
                }
            }
        } elseif ($grp !== -1) {
            $this->smarty->assign('catname', $grp);
        }

        if ($id === 'All' && $parentCategory === 'All') {
            $meta_title = 'Browse '.$parentCategory.' releases';
        } else {
            $meta_title = 'Browse '.$parentCategory.' / '.$id.' releases';
        }
        $meta_keywords = 'browse,nzb,description,details';
        $meta_description = 'Browse for Nzbs';

        $content = $this->smarty->fetch('browse.tpl');
        $this->smarty->assign(
            [
                'content' => $content,
                'covgroup' => $covgroup,
                'meta_title' => $meta_title,
                'meta_keywords' => $meta_keywords,
                'meta_description' => $meta_description,
            ]
        );
        $this->pagerender();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @throws \Exception
     */
    public function group(Request $request)
    {
        $this->setPrefs();
        $releases = new Releases();
        if ($request->has('g')) {
            $group = $request->input('g');
            $page = request()->has('page') && is_numeric(request()->input('page')) ? request()->input('page') : 1;
            $offset = ($page - 1) * config('nntmux.items_per_page');
            $rslt = $releases->getBrowseRange($page, [-1], $offset, config('nntmux.items_per_page'), '', -1, $this->userdata['categoryexclusions'], $group);
            $results = $this->paginate($rslt ?? [], $rslt[0]->_totalcount ?? 0, config('nntmux.items_per_page'), $page, request()->url(), request()->query());

            $browse = [];

            foreach ($results as $result) {
                $browse[] = $result;
            }

            $this->smarty->assign(
                [
                    'results' => $results,
                    'resultsadd' => $browse,
                    'parentcat' => $group,
                    'catname' => 'all',
                ]
            );
            $meta_title = 'Browse Groups';
            $meta_keywords = 'browse,nzb,description,details';
            $meta_description = 'Browse Groups';
            $content = $this->smarty->fetch('browse.tpl');

            $this->smarty->assign(
                [
                    'content' => $content,
                    'meta_title' => $meta_title,
                    'meta_keywords' => $meta_keywords,
                    'meta_description' => $meta_description,
                ]
            );

            $this->pagerender();
        }
    }
}