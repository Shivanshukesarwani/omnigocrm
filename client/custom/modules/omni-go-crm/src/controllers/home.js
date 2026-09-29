define('module:omni-go-crm/controllers/home', ['controller'], (Controller) => {
    return class extends Controller {
        index() {
            this.main('module:omni-go-crm/views/home', {});
        }
    };
});
