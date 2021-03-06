<?php

class actionContentItemBindList extends cmsAction {

    public function run(){

        $user = cmsUser::getInstance();

        $ctype_name       = $this->request->get('ctype_name', '');
        $child_ctype_name = $this->request->get('child_ctype_name', '');
        $item_id          = $this->request->get('id', 0);
        $authors          = $this->request->get('authors', '');
        $field            = $this->request->get('field', '');
        $text             = $this->request->get('text', '');
        $mode             = $this->request->get('mode', 'childs');

        if (!$ctype_name || !$child_ctype_name || !$authors || !$field){
            cmsCore::error404();
        }

        $ctype = $this->model->getContentTypeByName($ctype_name);
        if (!$ctype) { cmsCore::error404(); }

        $child_ctype = $this->model->getContentTypeByName($child_ctype_name);
        if (!$child_ctype) { cmsCore::error404(); }

		$relation = $this->model->getContentRelationByTypes($ctype['id'], $child_ctype['id']);
		if (!$relation) { cmsCore::error404(); }

        $is_allowed_to_add = cmsUser::isAllowed($child_ctype_name, 'add_to_parent');
        $is_allowed_to_bind = cmsUser::isAllowed($child_ctype_name, 'bind_to_parent');
        $is_allowed_to_unbind = cmsUser::isAllowed($child_ctype_name, 'bind_off_parent');

        if ($mode != 'unbind' && (!$is_allowed_to_add && !$is_allowed_to_bind)) {
            cmsCore::error404();
        }

        if ($mode == 'unbind' && !$is_allowed_to_unbind) {
            cmsCore::error404();
        }

        if ($text){
			$this->model->filterLike($field, "%{$text}%");
		}

        $this->model->limit(10);

		$perm = cmsUser::getPermissionValue($child_ctype_name, 'bind_to_parent');

		if ($mode == 'childs'){

            if ($perm == 'own_to_own' || $perm == 'own_to_all' || $authors == 'own'){
                $this->model->filterEqual('user_id', $user->id);
            }

            if ($item_id){
                $join_condition = "r.parent_ctype_id = {$ctype['id']} AND ".
                                  "r.parent_item_id = {$item_id} AND " .
                                  "r.child_ctype_id = {$child_ctype['id']} AND " .
                                  "r.child_item_id = i.id";

                $this->model->joinLeft('content_relations_bind', 'r', $join_condition);
                $this->model->filterIsNull('r.id');
            }

			$total = $this->model->getContentItemsCount($child_ctype_name);
			$items = $this->model->getContentItems($child_ctype_name);

		}

		if ($mode == 'parents'){

            if ($perm == 'own_to_own' || $perm == 'all_to_own' || $perm == 'other_to_own' || $authors == 'own'){
                $this->model->filterEqual('user_id', $user->id);
            }

            if ($perm == 'own_to_other' || $perm == 'all_to_other' || $perm == 'other_to_other'){
                $this->model->filterNotEqual('user_id', $user->id);
            }

            if ($item_id){
                $join_condition = "r.parent_ctype_id = {$ctype['id']} AND ".
                                  "r.parent_item_id = i.id AND " .
                                  "r.child_ctype_id = {$child_ctype['id']} AND " .
                                  "r.child_item_id = {$item_id}";

                $this->model->joinLeft('content_relations_bind', 'r', $join_condition);
                $this->model->filterIsNull('r.id');
            }

			$total = $this->model->getContentItemsCount($ctype_name);
			$items = $this->model->getContentItems($ctype_name);

		}

        if ($mode == 'unbind'){

            $unbind_perm = cmsUser::getPermissionValue($child_ctype_name, 'bind_off_parent');

            if ($unbind_perm == 'own' || $authors == 'own'){
                $this->model->filterEqual('user_id', $user->id);
            }

            if ($item_id){
                $join_condition = "r.parent_ctype_id = {$ctype['id']} AND ".
                                  "r.parent_item_id = {$item_id} AND " .
                                  "r.child_ctype_id = {$child_ctype['id']} AND " .
                                  "r.child_item_id = i.id";

                $this->model->joinLeft('content_relations_bind', 'r', $join_condition);
                $this->model->filterNotNull('r.id');
            }

			$total = $this->model->getContentItemsCount($child_ctype_name);
			$items = $this->model->getContentItems($child_ctype_name);

		}

        return $this->cms_template->render('item_bind_list', array(
            'mode'        => $mode,
            'ctype'       => $ctype,
            'child_ctype' => $child_ctype,
            'total'       => $total,
            'items'       => $items
        ));

    }

}
