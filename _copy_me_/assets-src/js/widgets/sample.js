export default function (Site, $) {

  Site.widget('demo', {
    _create () {
      console.log('Hey!')
    }
  })

}