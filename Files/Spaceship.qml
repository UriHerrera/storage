import QtQuick
import org.mauikit.controls as Maui

Canvas {
    id: ship
    implicitWidth: 32
    implicitHeight: implicitWidth
    property real flight: 0
    readonly property real thrust: 0.75 + 0.25 * Math.sin(flight * Math.PI * 24)
    readonly property color ink: Maui.Theme.textColor
    readonly property color flame: Maui.Theme.highlightColor
    onInkChanged: requestPaint()
    onFlameChanged: requestPaint()
    onFlightChanged: requestPaint()

    NumberAnimation on flight {
        from: 0
        to: 1
        duration: Maui.Style.units.longDuration * 12
        loops: Animation.Infinite
        running: ship.visible
    }

    onPaint: {
        const ctx = getContext("2d")
        ctx.reset()
        ctx.scale(width / 64, height / 64)

        ctx.fillStyle = ship.ink
        for (let i = 0; i < 12; ++i) {
            const near = i % 2 === 0
            const x = ((i * 29 + 128 - flight * (near ? 128 : 64)) % 64)
            const y = (i * 19 + 4) % 64
            ctx.globalAlpha = near ? 0.8 : 0.5
            ctx.fillRect(x, y, near ? 3 : 2, 2)
        }

        ctx.globalAlpha = 1
        ctx.translate(-28 + flight * 120, 32)
        ctx.rotate(Math.PI / 2)
        ctx.lineWidth = 2
        ctx.lineJoin = "round"
        ctx.lineCap = "round"
        ctx.strokeStyle = ship.ink
        ctx.beginPath()
        ctx.moveTo(0, -25)
        ctx.quadraticCurveTo(13, -13, 7, 10)
        ctx.lineTo(-7, 10)
        ctx.quadraticCurveTo(-13, -13, 0, -25)
        ctx.moveTo(9, -1)
        ctx.quadraticCurveTo(17, 4, 15, 16)
        ctx.lineTo(7, 10)
        ctx.moveTo(-9, -1)
        ctx.quadraticCurveTo(-17, 4, -15, 16)
        ctx.lineTo(-7, 10)
        ctx.stroke()
        ctx.beginPath()
        ctx.arc(0, -8, 4, 0, Math.PI * 2)
        ctx.stroke()
        ctx.strokeStyle = ship.flame
        ctx.beginPath()
        ctx.moveTo(-4, 14)
        ctx.quadraticCurveTo(-4, 18, 0, 16 + 11 * thrust)
        ctx.quadraticCurveTo(4, 18, 4, 14)
        ctx.stroke()
    }
}
