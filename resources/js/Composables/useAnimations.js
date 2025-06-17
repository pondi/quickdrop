import { ref } from 'vue'

export function useAnimations() {
    const beforeEnter = (el) => {
        el.style.opacity = 0
        el.style.transform = 'translateY(20px)'
    }
    
    const enter = (el, done) => {
        el.offsetHeight // force reflow
        el.style.transition = 'all 0.3s ease-out'
        el.style.opacity = 1
        el.style.transform = 'translateY(0)'
        done()
    }
    
    const leave = (el, done) => {
        el.style.transition = 'all 0.3s ease-in'
        el.style.opacity = 0
        el.style.transform = 'translateY(20px)'
        setTimeout(done, 300)
    }
    
    const slideInFromRight = {
        beforeEnter(el) {
            el.style.opacity = 0
            el.style.transform = 'translateX(100%)'
        },
        enter(el, done) {
            el.offsetHeight
            el.style.transition = 'all 0.3s ease-out'
            el.style.opacity = 1
            el.style.transform = 'translateX(0)'
            done()
        },
        leave(el, done) {
            el.style.transition = 'all 0.3s ease-in'
            el.style.opacity = 0
            el.style.transform = 'translateX(100%)'
            setTimeout(done, 300)
        }
    }
    
    const fadeScale = {
        beforeEnter(el) {
            el.style.opacity = 0
            el.style.transform = 'scale(0.9)'
        },
        enter(el, done) {
            el.offsetHeight
            el.style.transition = 'all 0.3s ease-out'
            el.style.opacity = 1
            el.style.transform = 'scale(1)'
            done()
        },
        leave(el, done) {
            el.style.transition = 'all 0.3s ease-in'
            el.style.opacity = 0
            el.style.transform = 'scale(0.9)'
            setTimeout(done, 300)
        }
    }
    
    const stagger = (delay = 50) => {
        return {
            beforeEnter(el) {
                el.style.opacity = 0
                el.style.transform = 'translateY(20px)'
            },
            enter(el, done) {
                const index = +el.dataset.index || 0
                setTimeout(() => {
                    el.style.transition = 'all 0.3s ease-out'
                    el.style.opacity = 1
                    el.style.transform = 'translateY(0)'
                    setTimeout(done, 300)
                }, index * delay)
            },
            leave(el, done) {
                const index = +el.dataset.index || 0
                setTimeout(() => {
                    el.style.transition = 'all 0.3s ease-in'
                    el.style.opacity = 0
                    el.style.transform = 'translateY(20px)'
                    setTimeout(done, 300)
                }, index * delay)
            }
        }
    }
    
    return {
        beforeEnter,
        enter,
        leave,
        slideInFromRight,
        fadeScale,
        stagger
    }
}

export function useTransitionClasses() {
    return {
        fadeSlide: {
            enterActiveClass: 'transition ease-out duration-300',
            enterFromClass: 'opacity-0 transform translate-y-4',
            enterToClass: 'opacity-100 transform translate-y-0',
            leaveActiveClass: 'transition ease-in duration-200',
            leaveFromClass: 'opacity-100 transform translate-y-0',
            leaveToClass: 'opacity-0 transform translate-y-4'
        },
        fade: {
            enterActiveClass: 'transition ease-out duration-300',
            enterFromClass: 'opacity-0',
            enterToClass: 'opacity-100',
            leaveActiveClass: 'transition ease-in duration-200',
            leaveFromClass: 'opacity-100',
            leaveToClass: 'opacity-0'
        },
        slideRight: {
            enterActiveClass: 'transition ease-out duration-300',
            enterFromClass: 'opacity-0 transform translate-x-full',
            enterToClass: 'opacity-100 transform translate-x-0',
            leaveActiveClass: 'transition ease-in duration-200',
            leaveFromClass: 'opacity-100 transform translate-x-0',
            leaveToClass: 'opacity-0 transform translate-x-full'
        },
        scale: {
            enterActiveClass: 'transition ease-out duration-300',
            enterFromClass: 'opacity-0 transform scale-90',
            enterToClass: 'opacity-100 transform scale-100',
            leaveActiveClass: 'transition ease-in duration-200',
            leaveFromClass: 'opacity-100 transform scale-100',
            leaveToClass: 'opacity-0 transform scale-90'
        }
    }
}