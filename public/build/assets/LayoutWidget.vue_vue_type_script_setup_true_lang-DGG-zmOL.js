import{K as b,L,C as y,M as g,Q as S,a2 as r,a3 as w,r as k,c as s,o as i,w as a,h as E,$ as l,E as u,n as $,a0 as C,a4 as _,I as T,f as x,b as c,a as p,u as A}from"./app-B8y3jIa7.js";import B from"./TopbarWidget-BIJjjSlQ.js";var P=b`
    .p-scrolltop.p-button {
        position: fixed !important;
        inset-block-end: 20px;
        inset-inline-end: 20px;
    }

    .p-scrolltop-sticky.p-button {
        position: sticky !important;
        display: flex;
        margin-inline-start: auto;
    }

    .p-scrolltop-enter-from {
        opacity: 0;
    }

    .p-scrolltop-enter-active {
        transition: opacity 0.15s;
    }

    .p-scrolltop.p-scrolltop-leave-to {
        opacity: 0;
    }

    .p-scrolltop-leave-active {
        transition: opacity 0.15s;
    }
`,D={root:function(t){var o=t.props;return["p-scrolltop",{"p-scrolltop-sticky":o.target!=="window"}]},icon:"p-scrolltop-icon"},V=L.extend({name:"scrolltop",style:P,classes:D}),I={name:"BaseScrollTop",extends:S,props:{target:{type:String,default:"window"},threshold:{type:Number,default:400},icon:{type:String,default:void 0},behavior:{type:String,default:"smooth"},buttonProps:{type:Object,default:function(){return{rounded:!0}}}},style:V,provide:function(){return{$pcScrollTop:this,$parentInstance:this}}},d={name:"ScrollTop",extends:I,inheritAttrs:!1,scrollListener:null,container:null,data:function(){return{visible:!1}},mounted:function(){this.target==="window"?this.bindDocumentScrollListener():this.target==="parent"&&this.bindParentScrollListener()},beforeUnmount:function(){this.target==="window"?this.unbindDocumentScrollListener():this.target==="parent"&&this.unbindParentScrollListener(),this.container&&(r.clear(this.container),this.overlay=null)},methods:{onClick:function(){var t=this.target==="window"?window:this.$el.parentElement;t.scroll({top:0,behavior:this.behavior})},checkVisibility:function(t){t>this.threshold?this.visible=!0:this.visible=!1},bindParentScrollListener:function(){var t=this;this.scrollListener=function(){t.checkVisibility(t.$el.parentElement.scrollTop)},this.$el.parentElement.addEventListener("scroll",this.scrollListener)},bindDocumentScrollListener:function(){var t=this;this.scrollListener=function(){t.checkVisibility(w())},window.addEventListener("scroll",this.scrollListener)},unbindParentScrollListener:function(){this.scrollListener&&(this.$el.parentElement.removeEventListener("scroll",this.scrollListener),this.scrollListener=null)},unbindDocumentScrollListener:function(){this.scrollListener&&(window.removeEventListener("scroll",this.scrollListener),this.scrollListener=null)},onEnter:function(t){r.set("overlay",t,this.$primevue.config.zIndex.overlay)},onAfterLeave:function(t){r.clear(t)},containerRef:function(t){this.container=t?t.$el:void 0}},computed:{scrollTopAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.scrollTop:void 0}},components:{ChevronUpIcon:g,Button:y}};function N(e,t,o,f,h,n){var m=k("Button");return i(),s(_,l({name:"p-scrolltop",appear:"",onEnter:n.onEnter,onAfterLeave:n.onAfterLeave},e.ptm("transition")),{default:a(function(){return[h.visible?(i(),s(m,l({key:0,ref:n.containerRef,class:e.cx("root"),onClick:n.onClick,"aria-label":n.scrollTopAriaLabel,unstyled:e.unstyled},e.buttonProps,{pt:e.ptm("root")}),{icon:a(function(v){return[u(e.$slots,"icon",{class:$(e.cx("icon"))},function(){return[(i(),s(C(e.icon?"span":"ChevronUpIcon"),l({class:[e.cx("icon"),e.icon,v.class]},e.ptm("root").icon,{"data-pc-section":"icon"}),null,16,["class"]))]})]}),_:3},16,["class","onClick","aria-label","unstyled","pt"])):E("",!0)]}),_:3},16,["onEnter","onAfterLeave"])}d.render=N;const U={class:"bg-surface-0 dark:bg-surface-900"},j={id:"home",class:"landing overflow-hidden"},R={class:"py-6 px-6 mx-0 md:mx-12 lg:mx-20 lg:px-20 flex items-center justify-between relative lg:static"},W=T({__name:"LayoutWidget",props:{user:Object},setup(e){const t=e;return(o,f)=>(i(),x("div",U,[c("div",j,[c("div",R,[p(B,{auth:t.user},null,8,["auth"])]),u(o.$slots,"default"),p(A(d))])]))}});export{W as _};
