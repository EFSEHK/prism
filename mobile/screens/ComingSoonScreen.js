import { View, Text, StyleSheet } from 'react-native'
import { ui } from '../components/ui'

export default function ComingSoonScreen({ title = 'Coming soon' }) {
  return (
    <View style={styles.root}>
      <View style={ui.heroCompact}>
        <Text style={ui.heroGreeting}>{title}</Text>
        <Text style={ui.heroMeta}>Coming soon</Text>
      </View>
    </View>
  )
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    padding: 16,
  },
})
