set :user,        "marc"
set :application, "The Whoot"
set :domain,      "thewhoot.com"
set :deploy_to,   "/srv/www/whoot"

set :repository,  "https://marbemac@github.com/whoot/whoot.git"
set :deploy_via,  :rsync_with_remote_cache
set :scm,         :git
# Or: `accurev`, `bzr`, `cvs`, `darcs`, `subversion`, `mercurial`, `perforce`, `subversion` or `none`

set :shared_files,      ["app/config/parameters.ini"]
set :shared_children,     [app_path + "/logs", web_path + "/uploads", "vendor"]

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain                         # This may be the same as your `Web` server
role :db,         domain, :primary => true       # This is where Rails migrations will run

set :dump_assetic_assets, true
#set   :use_sudo,      false
set  :keep_releases,  3

namespace :symfony do
  desc "Update the vendor libraries"
  task :update_vendors do
    run "cd #{latest_release} && #{php_bin} bin/vendors.php"
  end
end

before "deploy:finalize_update" do
  # share the children first (to get the vendor symlink)
  deploy.share_childs

  # update the vendors
  symfony.update_vendors
end